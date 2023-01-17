/*
 * POOL
 *
 * [P]HP [O]bject-[O]riented [L]ibrary
 *
 * ie.js created at 11.01.22, 14:44
 *
 * @author A.Manhart <alexander@manhart-it.de>
 */

/**
 * Nur im IE moeglich! Fuer die Intranetzone "ActiveX-Steuerelemente initialisieren und ausführen, die nicht als 'sicher für Skripting' markiert sind" aktivieren.
 *
 * @param url
 */
function startChrome(url) {
    // Parameter zusammenbauen
    //        var Parameter = "-foo -bar " + Parameter

    // Erzeugen des ActiveX Objekts
    let Shell = new ActiveXObject("WScript.Shell");

    // gewünschtes Programm starten
    Shell.Run('chrome.exe --no-proxy-server ' + url);
}