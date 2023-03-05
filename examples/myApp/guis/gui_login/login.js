function renderLoginSection(LOGGEDIN) {
    console.debug('rederLoginSection invoked');

    if (LOGGEDIN) {
        $('#loginSection > .logged-out').hide();
        $('#loginSection > .logged-in').show();
    } else {
        $('#loginSection > .logged-out').show();
        $('#loginSection > .logged-in').hide();
    }
}

function login() {
    console.debug('login invoked');

    var params = {
        'username': $('#username').val(),
        'password': $('#password').val()
    };
    RequestPOOL('GUI_Login', 'login', params, true, function(Result) {
        var success = Result['success'];

        if(success) {
            var PageUrl = new Url();
            PageUrl.setScript(SCRIPT_NAME);
            PageUrl.restartUrl();
        }
        else {
            alert('Falscher Benutzername und Passwort');
        }
    })
}

function logout() {
    console.debug('logout invoked');

    RequestPOOL('GUI_Login', 'logout', {}, true, function(Result) {
        renderLoginSection(false);
    })
}