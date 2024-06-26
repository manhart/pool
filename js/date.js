/**
 * -= date.js =-
 *
 * Erweitert die Funktionalit�t des JavaScript Objekts Date. Prototype ist erforderlich!
 * Nach DIN 1355 / ISO 8601
 *
 * $Log: date.js,v $
 *
 *
 * @version $Id: date.js 32281 2016-04-07 12:09:03Z manhart $
 * @version $Revision 1.0$
 * @version
 *
 * @since 2008/06/19
 * @author Alexander Manhart <alexander(dot)manhart(at)gmx(dot)de>
 * @link http://www.manhart.la
 */

Object.extend(Date.prototype, {
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
        function pad(num) { return num.toPaddedString(2); };

        return format.gsub(/\%([aAbBcdHImMpSVwuyY])/, function(part) {
            switch(part[1]) {
                case 'a': return $w(this.strings['days'][this.locale+'_short'])[day]; break;
                case 'A': return $w(this.strings['days'][this.locale])[day]; break;
                case 'b': return $w(this.strings['months'][this.locale+'_short'])[month]; break;
                case 'B': return $w(this.strings['months'][this.locale])[month]; break;
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
        }.bind(this));
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
     * Liefert true f�r ein Schaltjahr, andernfalls false
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
     * Liefert true f�r ein Wochenende, andernfalls false
     */
    isWeekend: function() {
        return (this.getDay() == 0 || this.getDay() == 6);
    },
    /**
     * Prueft ein beliebiges Datum auf Gueltigkeit
     */
    isValidDate: function(y, m, d) {
        if(y.length==2) {
            var now = new Date();
            y = now.getFullYear().toString().substring(0,2)+y.toString();
        }
        var dateEN = m + '/' + d + '/' + y;
        var ts = new Date(dateEN);

        if(ts.getDate() != d) {
            return false;
        }
        else if(ts.getMonth() != m-1) {
            //this is for the purpose JavaScript starts the month from 0
            return false;
        }
        else if(ts.getFullYear() != y) {
            return false;
        }
        if(this.isValidDate.arguments.length == 4) return ts.getTime();
        return true;
    },
    /**
     * Liest ein deutsches Datum ein dd.mm.YYYY HH:mm:ss
     */
    setGermanDate: function(string) {
        var parts = string.split(' ');
        if(parts[0]) {
            dateParts = parts[0].split('.');
            if(dateParts.length != 3) return false;
            var d = dateParts[0], m = dateParts[1], y = dateParts[2];
            var ts = this.isValidDate(y, m, d, true)
            if(ts == false) return false;
            this.setTime(ts);
        }
        else return false;
        if(parts[1]) {
            timeParts = parts[1].split(':');
            if(timeParts.length == 0) return false;
            if(timeParts[0]) this.setHours(timeParts[0]);
            if(timeParts[1]) this.setMinutes(timeParts[1]);
            if(timeParts[2]) this.setSeconds(timeParts[2]);
        }
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


function unixtime2date(unixtime) {
    return (new Date(unixtime * 1000)).toGMTString();
}

function date2unixtime(day, month, year, hours, minutes, seconds) {
    var humDate = new Date(Date.UTC(year, month, day, hours, minutes, seconds));
    return (humDate.getTime() / 1000.0);
}

function dateobject2unixtime(dateObject) {
    return date2unixtime(dateObject.getDate(), dateObject.getMonth(), dateObject.getFullYear(), dateObject.getHours(), dateObject.getMinutes(), dateObject.getSeconds());
}

/**
 * Prueft ein deutsches Datum auf Gueltigkeit
 */
function isValidDateDE(DE_date) {
    var datumParts = DE_date.split('.');
    if (datumParts.length == 3) {
        return isValidDate(datumParts[0], datumParts[1], datumParts[2]);
    } else return false;
}

/**
 * Prueft ein deutsches Datum auf Gueltigkeit
 */
function isValidDateUS(US_date) {
    var datumParts = US_date.split('-');
    if (datumParts.length == 3) {
        return isValidDate(datumParts[2], datumParts[1], datumParts[0]);
    } else return false;
}


/**
 * Prueft ein beliebiges Datum auf Gueltigkeit
 */
function isValidDate(d, m, y) {
    if (y.length == 2) {
        var now = new Date();
        y = now.getFullYear().toString().substring(0, 2) + y.toString();
    }
    var dateEN = m + '/' + d + '/' + y;
    var ts = new Date(dateEN);

    if (ts.getDate() != d) {
        return false;
    } else if (ts.getMonth() != m - 1) {
        //this is for the purpose JavaScript starts the month from 0
        return false;
    } else if (ts.getFullYear() != y) {
        return false;
    }

    return true;
}


function date_de_sort(a, b, order) {
    a = a.split('.')
    b = b.split('.')
    if (a[2] == b[2]) {
        if (a[1] == b[1])
            return (a[0] > b[0] ? 1 : -1) * (order == 'asc' ? 1 : -1);
        else
            return (a[1] > b[1] ? 1 : -1) * (order == 'asc' ? 1 : -1);
    } else
        return (a[2] > b[2] ? 1 : -1) * (order == 'asc' ? 1 : -1);
}