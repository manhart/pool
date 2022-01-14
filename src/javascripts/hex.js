/*
 * POOL
 *
 * [P]HP [O]bject-[O]riented [L]ibrary
 *
 * hex.js created at 11.01.22, 14:18
 *
 * @author A.Manhart <alexander@manhart-it.de>
 */

function String2Hex (str)
{
    var hex = "";
    for (i=0;i<str.length;i=i+1) {
        num = str.charCodeAt(i);
        hex += Num2Hex(num);
    }

    return hex;
}

function Hex2Num (hex)
{
    var num;
    var total;
    var i;

    total = 0;
    for (i=0;i<hex.length;i++) {
        letter = hex.substr(i,1);
        num = MakeNum(letter);
        exponent = ((hex.length-1)-i);
        total += num * Math.pow(16,exponent);
    }

    return total;
}

function Num2Hex (num)
{
    s=(num.toString(16));
    s=s.toUpperCase();
    return(s);
}

function Hex2String (hexstring)
{
    var str;
    var num;
    var hexval;

    totalnum = 0;
    str = "";
    for (i=0;i<hexstring.length;i=i+2) {
        hexval = hexstring.substr(i,2);
        num = Hex2Num(hexval);
        str += String.fromCharCode(num);
    }
    return str;
}