/*
 * POOL
 *
 * [P]HP [O]bject-[O]riented [L]ibrary
 *
 * host.js created at 11.01.22, 14:53
 *
 * @author A.Manhart <alexander@manhart-it.de>
 */

// wahr, wenn IP-Adresse als g�tig eingestuft wurde
function checkIPAddr(ipnr) {
    var iL = 0;
    var iC = 0;
    var i = 0;
    var sNr = "";

    for (; i < ipnr.length; i++) {
        if (ipnr.charAt(i) == '.') {
            if (!iL || (iL > 3) || parseInt(sNr, 10) > 255)
                return false;
            iC++;
            iL = 0;
            sNr = "";
            continue;
        }
        if (isDigit(ipnr.charAt(i))) {
            iL++;
            sNr = sNr + ipnr.charAt(i);
            continue;
        }
        return false;
    }

    if (parseInt(sNr, 10) > 255)
        return false;
    if (((iC == 3) && (iL >= 1) && (iL <= 3)) || ((iC == 4) && (!iL)))
        return true;
    else
        return false;
}

// wahr, wenn der Fully Qualified Domain Name als g�tig eingestuft wurde
function checkFQDN(fqdn) {
    var iL = 0;
    var iC = 0;
    var i = fqdn.length - 1;

    if ((fqdn.charAt(0) == '.') || (fqdn.charAt(0) == '-'))
        return false;
    if (fqdn.charAt(i) == '.')
        i = i - 1;

    for (; i >= 0; i--) {
        if (fqdn.charAt(i) == '.') {
            if (iL < 2 && iC < 2)
                return false;
            if (fqdn.charAt(i - 1) == '-')
                return false;
            iC++;
            iL = 0;
            continue;
        }
        if (isAlnum(fqdn.charAt(i))) {
            iL++;
            continue;
        }
        if (fqdn.charAt(i) == '-') {
            if (!iL)
                return false;
            iL++;
            continue;
        }
        return false;
    }

    if (!iC || (iL == 1 && iC < 2) || (!iL && iC == 1)) {
        return false;
    }
    return true;
}

// wahr, wenn der Hostname als g�tig eingestuft wurde
function checkHostname(hostname) {
    if (hostname.charAt(0) == '[') {
        if (hostname.charAt(hostname.length - 1) != ']')
            return false;
        var ipnr = hostname.substring(1, hostname.length - 1);
        return checkIpnr(ipnr);
    }

    if (hostname.charAt(0) == '#') {
        var nr = hostname.substring(1, hostname.length);
        return checkNr(nr);
    }

    return checkFqdn(hostname);
}