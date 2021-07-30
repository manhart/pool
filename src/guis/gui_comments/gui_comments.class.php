<?php
/* SQL Statement:
*
* create table tbl_Comments (
  id int primary key auto_increment,
  author int not null,
  text text not null,
  tablename varchar(16) not null,
  tableid int not null,
  createdate int not null
);
*/
class GUI_Comments extends GUI_Module
{
    public function __construct(&$Owner, $autoLoadFiles = false, array $params = [])
    {
        parent::__construct($Owner, false, $params);
    }

    function init($superglobals=I_EMPTY)
    {
        $this -> Defaults -> addVar('action', '');
        $this -> Defaults -> addVar('newsid', 0);
        $this -> Defaults -> addVar('text', '');
        $this -> Defaults -> addVar('post', 0);
        $this -> Defaults -> addVar('tablename', '');
        $this -> Defaults -> addVar('tableid', 0);
        $this -> Defaults -> addVar('tableid_var', 'tableid');
        $this -> Defaults -> addVar('session_userid_var', '');
        $this -> Defaults -> addVar('enableBox' ,'1');
        $this -> Defaults -> addVar('idComment', '');
        $this -> Defaults -> addVar('forwardUrl', null);
        $this -> Defaults -> addvar('noForward' , true);


        parent :: init(I_GET|I_POST);
    }

    function loadFiles()
    {
        $file_comments = $this -> Weblication -> findTemplate('tpl_comments.html', $this -> getClassName());
        $file_answer = $this -> Weblication -> findTemplate('tpl_answer.html', $this -> getClassName());
        $file_update = $this -> Weblication -> findTemplate('tpl_update.html', $this -> getClassName());

        $this -> Template -> setFilePath(
            array(
                'comments' => $file_comments,
                'answer' => $file_answer,
                'update' => $file_update
            )
        );
    }

    function prepare ()
    {
        $this -> loadFiles();

        $interfaces = & $this -> Weblication -> getInterfaces();
        $Session = & $this -> Session;
        $Input = & $this -> Input;
        $Template = & $this -> Template;




        if (method_exists($this -> Weblication -> Main, 'setActiveMenueItem')) {
            $this -> Weblication -> Main -> setActiveMenueItem('News');
        }

        $url = new Url();

        $tablename = $Input -> getVar('tablename');
        $tableid = $Input -> getVar($Input -> getVar('tableid_var'));
        $userid = $Session -> getVar($Input -> getVar('session_userid_var'));

        switch($Input -> getVar('action')) {
            case 'preview':
                if (trim($Input -> getVar('text')) == '' || $Input -> getVar('schema') == 'answer') {
                    $this -> disable(); break;
                }
                $boxTitle = 'Vorschau';
                $Template -> useFile('comments');
                $Template -> newBlock('COMMENT');
                $Template -> setVar('TEXT', nl2br($Input -> getVar('text')));
                $Template -> setVar('AUTHOR', $userid);
                $Template -> setVar('DATETIME', date('d.m.Y H:i', time()));
                break;

            // Antworten
            case 'answer':
                $text = trim($Input -> getVar('text'));
                if ($Input -> getVar('post') and $text != '' and $tableid) {
                    $dao_comments = & DAO::createDAO($interfaces, 'Intranet_tbl_Comments');
                    $result_comments = $dao_comments -> insert(array('author' => $userid, 'text' => $Input -> getVar('text'),
                        'tablename' => $tablename, 'tableid' => $tableid, 'createdate' => time()));
                    if ($result_comments -> getValue('last_insert_id') > 0) {

                        $url->setParam('schema', 'comments');
                        $forwardUrl = $Input -> getVar('forwardUrl');
                        $url -> InpGet -> setParams($forwardUrl);

                        if(!$Input -> getVar('noForward'))
                            $url -> gotoUrl();
                    }
                    else {
                        //error
                        trigger_error('Inserting comment failed!');
                        // TODO
                    }
                }

                $boxTitle = 'Antworten';
                $Template -> useFile('answer');
                $Template -> setVar('TEXT', $text);
                $url->setParam('action', null);
                $Template -> setVar('URL_SELF', $url -> getUrl());

                $url->setParam('schema', 'comments');
                $Template -> setVar('URL_BACK', $url -> getUrl());
                $Input -> setVar('tplhandle', 'answer');
                break;

            case 'update':
                $daoComment = & DAO::createDAO($interfaces, 'Intranet_tbl_Comments');
                $idComment = $Input -> getVar('idComment');
                $text = trim($Input -> getVar('text'));

                //echo "UPDATE : idComment = $idComment ; text = $text<br>";

                $url = new Url();
                $url->setParam('action', 'update');
                $url->setParam('schema', 'comments');
                $url->setParam('idComment',$idComment);

                // neu speichern
                if( !empty($text) && !empty($idComment) ) {
                    $data = array( 'id' => $idComment,
                                   'text' => $text
                    );
                    $anz =  $daoComment -> update($data);
                }

                // anzeigen
                $resComment = & $daoComment -> get( $idComment );
                $Template -> useFile('update');
                $Template -> setVar('TEXT', $resComment -> getValue('text') );
                $Template -> setVar('idComment', $idComment ) ;
                $Template -> setVar('URL_SELF', $url -> getUrl());
                $Template -> setVar( $Input -> getData() );

                break;

            case delete:


                $daoComment = & DAO::createDAO($interfaces, 'Intranet_tbl_Comments');
                $idComment = $Input -> getVar('idComment');
                if(!empty($idComment)) {
                    $daoComment -> delete($idComment);
                }

                $url = new Url();
                $url->setParam('schema', 'comments');
                $forwardUrl = $Input -> getVar('forwardUrl');
                if(!empty($forwardUrl))
                    $url -> InpGet -> setParams($forwardUrl);
                $url -> gotoUrl();

                break;

            case 'single':

                // nur einen bestimmten Kommentar anzeigen - anhand der �bergebenen id (idComment) .
                $this -> Template -> useFile('comments');
                $dao_comments = & DAO::createDAO($interfaces, 'Intranet_tbl_Comments');
                $result_comments = $dao_comments -> get($Input -> getVar('idComment') );

                if( $result_comments -> count() == 1) {
                    $this -> Template -> newBlock('COMMENT');
                    $this -> Template -> setVar('TEXT', nl2br($result_comments -> getValue('text')));
                    $this -> Template -> setVar('AUTHOR', $result_comments -> getValue('author'));
                    $this -> Template -> setVar('DATETIME', strftime('%d.%m.%Y %H:%M', $result_comments -> getValue('createdate')));

                } else {
                    echo "Parameter (idComment) not found or specified!";
                }

                break;

            // Kommentare anzeigen
            default:
                $boxTitle = 'Kommentare';
                $this -> Template -> useFile('comments');

                $dao_comments = & DAO::createDAO($interfaces, 'Intranet_tbl_Comments');
                $result_comments = $dao_comments -> getMultiple(null, null, array(
                    0 => array('tableid', 'equal', $tableid),
                    1 => array('tablename', 'equal', $tablename)),
                    array('createdate' => 'DESC'));

                if ($result_comments -> count() > 0) {
                    for ($i=0; $i<$result_comments -> count(); $i++) {
                        $this -> Template -> newBlock('COMMENT');
                        $this -> Template -> setVar('TEXT', nl2br($result_comments -> getValue('text')));
                        $this -> Template -> setVar('AUTHOR', $result_comments -> getValue('author'));
                        $this -> Template -> setVar('DATETIME', strftime('%d.%m.%Y %H:%M', $result_comments -> getValue('createdate')));
                        $result_comments -> next();
                    }
                }
                else {
                    $this -> Template -> newBlock('NOCOMMENT');
                }

                $this -> Template -> newBlock('ButtonAnswer');
                $url->setParam('schema', 'answer');
                $this -> Template -> setVar('URL_ANSWER', $url -> getUrl());
                $Input -> setVar('tplhandle', 'comments');
                $this -> Template -> leaveBlock();
        }

        if($Input -> getVar('enableBox')) {
            $this -> enableBox($boxTitle, 'tpl_box.html');
        }


        $this -> addHandoffVar($Input -> getData());
    }

    function finalize()
    {
        $this -> Template -> parse($this -> Input -> getVar('tplhandle'));
        return $this -> reviveChildGUIs($this -> Template -> getContent($this -> Input -> getVar('tplhandle')));
    }
}