/*
* Cookies
* Rightly or wrongly people are wary of cookies, and as a result they sometimes set the options on their browser to warn them 
* if a site attempts to write a cookie. The user can then decide whether to allow the site to write the cookie or not. 
* However badly written scripts could still attempt to write the same cookie or other further cookies without realising that the 
* visitor doesn't want them. If this goes on for too long then the visitor may leave - disaster! 
* 
* The browser can warn if a cookie is being written, however it remains silent if the cookie is being read. It is this facility that 
* will be used to create Intelligent Cookies. 
* 
* Normally when writing a cookie - all that is done is the writing of the cookie. If we were to check to see if the cookie had actually 
* been written then we would know whether the visitor is accepting cookies. Before writing the next cookie, we should check that the 
* last cookie was written successfully. If it wasn't then we shouldn't write the next one. 
* 
* Rather than use JavaScript variables to work out whether the last cookie was written or not, we'll use a Master Cookie itself as an 
* indication of whether or not any further cookies should be written. 
* 
* If the visitor accepts the first few cookies, but then stops accepting them, for whatever reason, then we should realise this and 
* stop the writing of any further cookies. In this instance we need to delete the Master Cookie. This will then indicate that no further 
* cookies are acceptable. Note: it is possible that the visitor could deny this as well. 
* 
* The following psuedo code explains the mechanism: 
* 
* store Master Cookie
*     get Master Cookie
*     if Master Cookie does not exist
*         set Master Cookie
* 
* store Intelligent Cookie
*     get Master Cookie
*     if Master Cookie
*         get Intelligent Cookie
*         if Intelligent Cookie does not exist or its value is different
*             set Intelligent Cookie
*             get Intelligent Cookie
*             if Intelligent Cookie does not exist or its value is different
*                 delete Master Cookie
* The store Master Cookie code only needs to be invoked from the sites Home Page, i.e. when the visitor arrives. The store Intelligent Cookie 
* code can be invoked any time an Intelligent Cookie needs to be stored. 
* 
* The Master Cookie will be stored without an expiry date, which means that once the visitors current browser session has 
* finished the cookie will disappear. Next time the visitor comes by the Master Cookie will be stored again. If the visitor refuses to 
* accept cookies, then the worse they'll receive is a request to store the Master Cookie each time they visit the Home Page.

function GetCookie(name) 
{
    var start = document.cookie.indexOf(name+"=");
    var len = start+name.length+1;
    if ((!start) && (name != document.cookie.substring(0,name.length))) return null;
    if (start == -1) return null;
    var end = document.cookie.indexOf(";",len);
    if (end == -1) end = document.cookie.length;
    return unescape(document.cookie.substring(len,end));
}

function SetCookie(name,value,expires,path,domain,secure) 
{
    document.cookie = name + "=" +escape(value) +
        ( (expires) ? ";expires=" + expires.toGMTString() : "") +
        ( (path) ? ";path=" + path : "") + 
        ( (domain) ? ";domain=" + domain : "") +
        ( (secure) ? ";secure" : "");
}

function DeleteCookie(name,path,domain) 
{
    if (Get_Cookie(name)) document.cookie = name + "=" +
       ( (path) ? ";path=" + path : "") +
       ( (domain) ? ";domain=" + domain : "") +
       ";expires=Thu, 01-Jan-70 00:00:01 GMT";
}

var today = new Date();
var zero_date = new Date(0,0,0);
today.setTime(today.getTime() - zero_date.getTime());

var todays_date = new Date(today.getYear(), today.getMonth(), today.getDate(),0,0,0);
var expires_date = new Date(todays_date.getTime() + (8 * 7 * 86400000));

function storeMasterCookie() 
{
    if (!GetCookie('MasterCookie'))
        SetCookie('MasterCookie','MasterCookie');
}

function storeIntelligentCookie(name,value) 
{
    if (GetCookie('MasterCookie')) {
        var IntelligentCookie = GetCookie(name);
        if ((!IntelligentCookie) || (IntelligentCookie != value)) {
            SetCookie(name, value, expires_date);
            var IntelligentCookie = GetCookie(name);
            if ((!IntelligentCookie) || (IntelligentCookie != value))
                DeleteCookie('MasterCookie');
        }
    }
}