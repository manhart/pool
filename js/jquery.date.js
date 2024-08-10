/**
 * -= jquery.date.js =-
 *
 * Erweitert die Funktionalität des JavaScript Objekts Date. Prototype ist erforderlich!
 * Nach DIN 1355 / ISO 8601
 *
 * $Log: date.js,v $
 *
 * @deprecated
 * @version $Id: jquery.date.js 32281 2016-04-07 12:09:03Z manhart $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2011/04/11
 * @author Alexander Manhart <alexander(dot)manhart(at)gmx(dot)de>
 * @link http://www.manhart.la
 */

jQuery.extend(Date.prototype, {
    day_in_ms: 24*60*60*1000,
    week_in_ms: 7*24*60*60*1000,
    locale: 'de',
    strings: {
        'days': {
            'de'       : 'Sonntag Montag Dienstag Mittwoch Donnerstag Freitag Samstag',
            'de_short' : 'So Mo Di Mi Do Fr Sa',
            'en'       : 'Sunday Monday Tuesday Wednesday Thursday Friday Saturday',
            'en_short' : 'Sun Mon Tue Wed Thu Fri Sat',
            'fr'       : 'Dimanche Lundi Mardi Mercredi Jeudi Vendredi Samedi',
            'fr_short' : '??'
        },
        'months': {
            'de'       : 'Januar Februar März April Mai Juni Juli August September Oktober November Dezember',
            'de_short' : 'Jan Feb Mär Apr Mai Jun Jul Aug Sep Okt Nov Dez',
            'en'       : 'January February March April May June July August September October November December',
            'en_short' : 'Jan Feb Mar Apr May Jun Jul Aug Sep Oct Nov Dec',
            'fr'       : 'Janvier Février Mars Avril Mai Juin Juillet Août Septembre Octobre Novembre Décembre',
            'fr_short' : '??'
        }
    },
    test: function() {
        alert('okay');
    },
    /**
     * Setzt lokale Informationen
     */
    setLocale: function(locale) {
        this.locale = locale;
    },
    /**
     * Gibt das Datum entsprechend dem Parameter "format" formatiert zurück
     */
    strftime: function(format) {
        var day = this.getDay(), month = this.getMonth();
        var hours = this.getHours(), minutes = this.getMinutes();

        // privat (benutzt jquery.helpers.js fn:pad)
        var pad = function (num) {
            return num.toString().pad(2);
        };

        // Wrap this
        var that = this;

        // privat
        var fot = function(part) {
            switch(part[1]) {
                case 'a': return this.strings['days'][this.locale+'_short'].split(' ')[day]; break;
                case 'A': return this.strings['days'][this.locale].split(' ')[day]; break;
                case 'b': return this.strings['months'][this.locale+'_short'].split(' ')[month]; break;
                case 'B': return this.strings['months'][this.locale].split(' ')[month]; break;
                case 'c': return this.toString(); break;
                case 'd': return pad(this.getDate()); break;
                case 'H': return pad(hours); break;
                case 'I': return pad((hours + 12) % 12); break;
                case 'm': return pad(month + 1); break;
                case 'M': return pad(minutes); break;
                case 'p': return hours > 12 ? 'PM' : 'AM'; break;
                case 'S': return pad(this.getSeconds()); break;
                case 'V': return pad(this.getWeekNumber()); break;
                case 'w': return day; break;
                case 'u': return (day == 0) ? 7 : day; break;
                case 'y': return pad(this.getFullYear() % 100); break;
                case 'Y': return this.getFullYear().toString(); break;
            }
        };

        // benutzt gsub aus jquery.helpers.js fn:gsub)
        return format.gsub(/\%([aAbBcdHImMpSVwuyY])/, function() {
            return fot.apply(that, arguments)
        });
    },
    __diffSummertime_in_ms: function() {
        return (((new Date(this.getFullYear(), this.getMonth(), this.getDate()).getTimezoneOffset())-(new Date(this.getFullYear(), 0, 1).getTimezoneOffset()) ))*60*1000*-1;
    },
    /**
     * Gibt true in der Sommerzeit zurück, andernfalls false
     */
    isSummertime: function() {
        return (this.__diffSummertime_in_ms()>0);
    },
    /**
     * Liefert den Montag als Date
     */
    getMonday: function() {
        var Monday = new Date(Number(this)-((this.getDay()+6)%7)*this.day_in_ms);
        Monday.setHours(0);
        Monday.setMinutes(0);
        Monday.setSeconds(0);
        Monday.setMilliseconds(0);
        return Monday;
    },
    /**
     * Liefert den Dienstag als Date
     */
    getTuesday: function() {
        return new Date(this.getMonday().getTime()+this.day_in_ms);
    },
    /**
     * Liefert den Mittwoch als Date
     */
    getWednesday: function() {
        return new Date(this.getMonday().getTime()+(2*this.day_in_ms));
    },
    /**
     * Liefert den Donnerstag als Date
     */
    getThursday: function() {
        return new Date(this.getMonday().getTime()+(3*this.day_in_ms));
    },
    /**
     * Liefert den Freitag als Date
     */
    getFriday: function() {
        return new Date(this.getMonday().getTime()+(4*this.day_in_ms));
    },
    /**
     * Liefert den Samstag als Date
     */
    getSaturday: function() {
        return new Date(this.getMonday().getTime()+(5*this.day_in_ms));
    },
    /**
     * Liefert den Sonntag als Date
     */
    getSunday: function() {
        return new Date(this.getMonday().getTime()+(6*this.day_in_ms));
    },
    /**
     * Liefert die Kalenderwoche als Zahl
     */
    getWeekNumber: function() {
        var firstThursdayDate = new Date(this.getThursday().getFullYear(), 0, 4).getThursday(); // Do, in der KW1
        var currentThursdayDate = new Date(this.getFullYear(), this.getMonth(), this.getDate()).getThursday(); // Donnerstag dieser Woche
        return (currentThursdayDate.getTime()+this.__diffSummertime_in_ms()-firstThursdayDate.getTime())/(this.week_in_ms)+1;
    },
    /**
     * Liefert die erste Kalenderwoche eines Jahres beginnend mit Montag als Date
     */
    getFirstWeek: function() {
        return new Date(this.getThursday().getFullYear(), 0, 4).getMonday(); // Mo, in der KW1
    },
    /**
     * Liefert die letzte Kalenderwoche eines Jahres beginnend mit Montag als Date
     */
    getLastWeek: function() {
        return new Date(Number(new Date(this.getFullYear()+1, 0, 4).getMonday())-this.week_in_ms); // Mo, der letzten KW 52/53
    },
    /**
     * Liefert die Anzahl Wochen eines Jahres
     */
    getNumberOfWeeks: function() {
        return this.getLastWeek().getWeekNumber();
    },
    /**
     * Liefert true für ein Schaltjahr, andernfalls false
     */
    isLeapYear: function() {
        var y = this.getFullYear();
        if(y < 1000) return false;
        if(y < 1582) return (y % 4 == 0); // vor Gregorio XIII - 1582
        return (((y % 4 == 0) && (y % 100 != 0)) || (y % 400 == 0)); // nach Gregorio XIII
    },
    /**
     * Liefert die Anzahl Tage eines Jahres
     */
    getNumberOfDaysOfYear: function() {
        return (this.isLeapYear()) ? 366 : 365;
    },
    /**
     * Liefert true für ein Wochenende, andernfalls false
     */
    isWeekend: function() {
        return (this.getDay() == 0 || this.getDay() == 6);
    },
    /**
     * Prueft ein beliebiges Datum auf Gueltigkeit
     */
    isValidDate: function(y, m, d) {
        if(y.length == 2) {
            var now = new Date();
            y = now.getFullYear().toString().substring(0,2)+y.toString();
        }

        var ts = new Date(y, m, d, 0, 0, 0);

        if(ts.getDate() != d) {
            return false;
        }
        else if(ts.getMonth() != m) {
            //this is for the purpose JavaScript starts the month from 0
            return false;
        }
        else if(ts.getFullYear() != y) {
            return false;
        }
        if(this.isValidDate.arguments.length == 4) {
            return ts.getTime();
        }

        return true;
    },
    /**
     * Liest ein deutsches Datum ein dd.mm.YYYY HH:mm:ss
     */
    setGermanDate: function(string) {
        var parts = string.split(' ');
        if(parts[0]) {
            var dateParts = parts[0].split('.');
            if(typeof(dateParts[1]) == 'undefined') dateParts[1] = 0;
            if(typeof(dateParts[2]) == 'undefined') dateParts[2] = 0;
            var d = parseInt(dateParts[0], 10), m = parseInt(dateParts[1], 10)-1, y = dateParts[2];
            var ts = this.isValidDate(y, m, d, true)
            if(ts == false) return false;
            this.setTime(ts);
        }
        else {
            return false;
        }
        // setze Zeit
        var h = 0;
        var m = 0;
        var s = 0;
        var ms = 0;
        if(parts[1]) {
            var timeParts = parts[1].split(':');
            if(timeParts.length == 0) return false;
            if(timeParts[0]) h = timeParts[0];
            if(timeParts[1]) m = timeParts[1];
            if(timeParts[2]) s = timeParts[2];
        }
        this.setHours(h);
        this.setMinutes(m);
        this.setSeconds(s);
        this.setMilliseconds(ms);
        return this;
    },
    setDateTime: function(string) {
        var parts = string.split(' ');
        if(parts[0]) {
            var dateParts = parts[0].split('-');
            if(typeof(dateParts[1]) == 'undefined') dateParts[1] = 0;
            if(typeof(dateParts[2]) == 'undefined') dateParts[2] = 0;
            var y = parseInt(dateParts[0], 10), m = parseInt(dateParts[1], 10)-1, d = dateParts[2];
            var ts = this.isValidDate(y, m, d, true)
            if(ts == false) {
                return false;
            }
            this.setTime(ts);
        }
        else {
            return false;
        }
        // setze Zeit
        var h = 0;
        var m = 0;
        var s = 0;
        var ms = 0;
        if(parts[1]) {
            var timeParts = parts[1].split(':');
            if(timeParts.length == 0) return false; // ungueltige Zeit
            if(timeParts[0]) h = timeParts[0];
            if(timeParts[1]) m = timeParts[1];
            if(timeParts[2]) s = timeParts[2];
        }
        this.setHours(h);
        this.setMinutes(m);
        this.setSeconds(s);
        this.setMilliseconds(ms);
        return this;
    },
    /**
     * Liefert Unix Timestamp
     */
    getUnixTimestamp: function() {
        return this.getTime()/1000;
    }
});

/**
 * Datum-/Zeit Sortierung z.B. fuer das dhtmlxGrid
 */
function datetime_de_sort(a, b, order) {
    if(a != '') {
        a = new Date().setGermanDate(a);
        a = a.getUnixTimestamp();
    }
    else a = 0;
    if(b != '') {
        b = new Date().setGermanDate(b);
        b = b.getUnixTimestamp();
    }
    else b = 0;

    var retval = 0;
    if(a > b) {
        retval = 1;
    }
    else if(a < b) {
        retval = -1;
    }
    if(order != 'asc') retval = retval * -1;
    return retval;
}

/**
 * Datum-/Zeit Sortierung z.B. fuer das dhtmlxGrid - schneidet jedoch den Rest nach dem Datum weg
 */
function datetime_de_sort_cutWeekday(a, b, order)
{
    return datetime_de_sort(a.substr(0, 10), b.substr(0, 10), order);
}

/**
 * Berechnet das Alter
 */
function calcAge(Birthdate) {
    var Now = new Date();
    var alter = parseInt(Now.getFullYear() - Birthdate.getFullYear());
    if(Birthdate.getMonth() > Now.getMonth()) {
        alter -= 1;
    }
    else if(Birthdate.getMonth() == Now.getMonth()) {
        if(Birthdate.getDate() > Now.getDate()) {
            alter -= 1;
        }
    }
    return alter;
}