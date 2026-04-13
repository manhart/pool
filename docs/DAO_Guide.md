# DAO – Leitfaden

Dieses Dokument beschreibt die Datenbankzugriffs-Schicht (Data Access Object) des POOL-Frameworks. Schwerpunkt liegt auf dem Relation- und Join-System.

---

## Grundaufbau

Jeder DAO erbt von `pool\classes\Database\DAO` und beschreibt eine Datenbanktabelle über statische Properties:

```php
class Order extends DAO
{
    protected static ?string $databaseName = 'shop';
    protected static ?string $tableName    = 'Order';

    protected array $pk      = ['idOrder'];
    protected array $columns = ['idOrder', 'idCustomer', 'idItem', 'total'];
}
```

Instanz erzeugen und Datensätze abfragen:

```php
$orders = Order::create()->getMultiple(
    filter:  [['total', Operator::greater, 100]],
    sorting: ['total' => 'DESC'],
    limit:   [0, 20],
);
```

---

## Relations – Überblick

Joins werden nicht im Application Layer als SQL formuliert, sondern als Metadaten am DAO deklariert. Das Framework erkennt referenzierte Tabellen automatisch aus Spaltenangaben im Query und fügt die nötigen JOINs selbst hinzu.

Es gibt drei Ebenen, die in dieser Reihenfolge zusammengeführt werden (`array_replace`-Semantik — spätere Ebenen überschreiben frühere):

| Ebene | Property | Wer befüllt | Lebensdauer |
|---|---|---|---|
| 1 | `$generatedRelations` | pool-cli (aus FK-Schema generiert) | statisch in der Klasse |
| 2 | `$customRelations` | Entwickler (manuell) | statisch in der Klasse |
| 3 | Runtime | `join()`-Aufruf | nur die aktuelle Query |

---

## `$generatedRelations` — vom pool-cli generiert

pool-cli liest die Fremdschlüssel der Tabelle aus und erzeugt dieses Array automatisch. **Nicht manuell bearbeiten** — Änderungen werden bei der nächsten Generierung überschrieben.

```php
// Generiert aus: ORDER.idCustomer → CUSTOMER.idCustomer
protected array $generatedRelations = [
    'Customer' => [
        'target'    => Customer::class,
        'columnMap' => ['idCustomer' => 'idCustomer'],
    ],
    'Item' => [
        'target'    => Item::class,
        'columnMap' => ['idItem' => 'idItem'],
    ],
];
```

**Key-Vergabe bei mehreren FKs auf dieselbe Tabelle:**
pool-cli nutzt die Spalten-Namenskonvention `id{Tabelle}_{Alias}`. Aus `idAddress_ShipTo` und `idAddress_BillTo` entstehen die Keys `ShipTo` und `BillTo`:

```php
protected array $generatedRelations = [
    'ShipTo' => [
        'target'    => Address::class,
        'columnMap' => ['idAddress_ShipTo' => 'idAddress'],
    ],
    'BillTo' => [
        'target'    => Address::class,
        'columnMap' => ['idAddress_BillTo' => 'idAddress'],
    ],
];
```

Ist die Konvention nicht eingehalten, wird nur der erste FK generiert und eine Warnung ausgegeben.

---

## `$customRelations` — manuell definiert

Für Aliase, abweichende Join-Typen, polymorphe Bedingungen oder verkettete Joins. Gleiche Struktur wie `$generatedRelations`; ein Key mit gleichem Namen überschreibt den generierten Eintrag.

### Einfacher Alias mit abweichendem Join-Typ

```php
protected array $customRelations = [
    // Überschreibt generierten Key 'ShipTo' — jetzt INNER statt LEFT
    'ShipTo' => [
        'target'    => Address::class,
        'columnMap' => ['idAddress_ShipTo' => 'idAddress'],
        'joinType'  => JoinType::inner,
    ],
];
```

### Polymorphe Bedingung mit `on`-Array

Wenn keine echte FK-Beziehung existiert (z.B. eine generische Kommentar-Tabelle), wird die ON-Bedingung als Array angegeben:

- `left` — Spalte der gejointen Tabelle (linke Seite)
- `operator` — `Operator`-Enum
- `value` — Literalwert (wird escaped und gequoted)
- `right` — Spaltenreferenz (wird **nicht** escaped; unterstützt `{root}` und `{source}`)

```php
protected array $customRelations = [
    'Comment' => [
        'target' => Comment::class,
        'on'     => [
            ['left' => 'groupName',  'operator' => Operator::equal, 'value' => 'Order'],
            ['left' => 'groupRowId', 'operator' => Operator::equal, 'right' => '{root}.idOrder'],
        ],
        'ddl' => false, // kein FOREIGN KEY in DDL — nur logische Beziehung
    ],
];
```

Erzeugt:
```sql
LEFT JOIN shop.Comment AS `Comment`
    ON (`Comment`.groupName = 'Order' AND `Comment`.groupRowId = Order.idOrder)
```

### `on` als String (nur in `$customRelations`)

Für komplexe Bedingungen, die sich nicht als Array ausdrücken lassen (z.B. OR-Verknüpfungen):

```php
protected array $customRelations = [
    'ActiveShipTo' => [
        'target' => Address::class,
        'on'     => 'ActiveShipTo.idAddress = {root}.idAddress_ShipTo AND ActiveShipTo.active = 1',
    ],
];
```

### Platzhalter

| Platzhalter | Bedeutet |
|---|---|
| `{root}` | Alias/Tabellenname des aufrufenden DAOs |
| `{source}` | Alias der in `source` referenzierten Relation (für Ketten) |

### Verkettete Joins (`source`)

Wenn ein Join nicht vom Root-DAO ausgeht, sondern von einer anderen Relation:

```php
protected array $customRelations = [
    // ShipTo bereits in generatedRelations
    'DeliveryCountry' => [
        'source'    => 'ShipTo',           // JOIN von ShipTo aus, nicht von Order
        'target'    => Country::class,
        'columnMap' => ['idCountry' => 'idCountry'],
    ],
];
```

Erzeugt:
```sql
LEFT JOIN shop.Address AS `ShipTo`
    ON (`Order`.`idAddress_ShipTo` = `ShipTo`.`idAddress`)
LEFT JOIN geo.Country AS `DeliveryCountry`
    ON (`ShipTo`.`idCountry` = `DeliveryCountry`.`idCountry`)
```

`ShipTo` wird automatisch mit in die Join-Menge aufgenommen, auch wenn es nicht explizit angefordert wurde.

---

## Automatische Join-Erkennung

Der häufigste Anwendungsfall: der Application Layer schreibt einfach qualifizierte Spaltennamen — das Framework erkennt die Tabellen-Prefixe und löst die Joins automatisch auf.

Prefixe werden aus diesen Query-Parametern extrahiert:

| Parameter | Beispiel |
|---|---|
| `filter` / `having` | `['Customer.name', Operator::like, 'Muster%']` |
| `sorting` | `['Customer.name' => 'ASC']` |
| `groupBy` | `['Item.category' => 'ASC']` |
| `columns` | `Order::createWithColumns('Order.total', 'Customer.name')` |

```php
// Kein expliziter Join-Aufruf nötig — 'Customer' und 'Item' werden automatisch gejoint
$result = Order::create()->getMultiple(
    filter:  [['Customer.country', Operator::equal, 'DE']],
    sorting: ['Item.name' => 'ASC'],
);
```

Voraussetzung: Der Prefix muss als Key in `getRelations()` vorhanden sein (d.h. in `$generatedRelations` oder `$customRelations`).

### Auto-Join deaktivieren

Wenn die Erkennung stört (z.B. qualifizierte Spalten werden zwar im Query verwendet, sollen aber nicht implizit joinen), gibt es zwei Abstufungen:

**Pro Query** — `withoutAutoJoin()`:

```php
$result = Order::create()
    ->withoutAutoJoin()
    ->with('Customer')                                    // explizit erzwingen
    ->getMultiple(filter: [['Item.category', Operator::equal, 'A']]);
// 'Item.category' im Filter triggert KEINEN JOIN — nur 'Customer' wird gejoint.
```

Das Flag wird nach der Query automatisch zurückgesetzt.

**Pro DAO-Klasse** — `$autoJoin`-Property:

```php
class Order extends DAO
{
    protected bool $autoJoin = false;   // gilt für alle Queries dieses DAOs
    // ...
}
```

In beiden Fällen funktionieren `with()` und `join()` weiterhin — nur die Prefix-Erkennung aus Query-Parametern ist abgeschaltet.

---

## `with()` — explizite Anforderung

Wenn ein Join gebraucht wird, aber kein Prefix im Query vorkommt (z.B. nur SELECT-Spalten ohne Qualifizierung):

```php
$result = Order::create()
    ->setColumns('total')
    ->with('Customer', 'Item')   // Customer und Item werden gejoint, obwohl im filter kein Prefix steht
    ->getMultiple(filter: [['total', Operator::greater, 0]]);
```

`with()` und automatische Erkennung ergänzen sich — die finale Join-Menge ist die Vereinigung beider.

---

## `join()` — ad-hoc Runtime-Relation

Für Joins, die weder in `$generatedRelations` noch in `$customRelations` stehen. Gleiche Struktur wie ein `$customRelations`-Eintrag. Wird nach der Query automatisch zurückgesetzt.

```php
$result = Order::create()
    ->join('Audit', [
        'target'   => AuditLog::class,
        'on'       => [
            ['left' => 'entityType', 'operator' => Operator::equal, 'value' => 'Order'],
            ['left' => 'entityId',   'operator' => Operator::equal, 'right' => '{root}.idOrder'],
        ],
        'joinType' => JoinType::left,
        'ddl'      => false,
    ])
    ->getMultiple(filter: [['Audit.changedBy', Operator::equal, $userId]]);
```

---

## `getRelations()` — Auflösungsreihenfolge

```php
public function getRelations(): array
{
    return array_replace(
        $this->getGeneratedRelations(),  // Ebene 1
        $this->getCustomRelations(),     // Ebene 2 — überschreibt Ebene 1
        $this->getRuntimeRelations(),    // Ebene 3 — überschreibt Ebene 1 und 2
    );
}
```

Ein Key, der in mehreren Ebenen vorkommt, nimmt immer die Definition der höchsten Ebene an.

---

## `ddl`-Flag

Gibt an, ob diese Relation bei der DDL-Generierung als `FOREIGN KEY`-Constraint berücksichtigt werden soll. Die DDL-Generierung selbst ist noch nicht finalisiert — ob sie vom DAO, von pool-cli oder einem separaten Werkzeug übernommen wird, ist offen.

| Situation | `ddl` default |
|---|---|
| `columnMap` ohne `on` | `true` |
| `on` enthält `value`-Einträge | `false` |
| `on` enthält nur `right` | `true` |
| Explizit gesetzt | überschreibt Default |

Logische/polymorphe Relationen immer mit `'ddl' => false` markieren.

---

## `JoinType`

```php
enum JoinType: string {
    case left  = 'LEFT';
    case right = 'RIGHT';
    case inner = 'INNER';
    case cross = 'CROSS';
}
```

Default ist `JoinType::left`. Abweichungen werden im Relation-Array angegeben:

```php
'Item' => [
    'target'    => Item::class,
    'columnMap' => ['idItem' => 'idItem'],
    'joinType'  => JoinType::inner,
],
```
