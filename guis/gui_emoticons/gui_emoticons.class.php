<?php
/**
 * Class GUI_Emoticons
 *
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/*
*
* create table tbl_Emoticons (
  id int primary key auto_increment,
  ident varchar(24) not null,
  filename varchar(255) not null,
  caption varchar(32) not null,
  sort smallint not null
)
*/
class GUI_Emoticons extends GUI_Module
{
    function init(?int $superglobals=I_EMPTY)
    {
        $this -> Defaults -> addVar('memo', '');
        $this -> Defaults -> addVar('maxcols', 5);
        $this -> Defaults -> addVar('tabledefine', '');


        parent :: init(I_EMPTY);
    }

    function loadFiles()
    {
        $file_emoticons = $this -> Weblication -> findTemplate('tpl_emoticons.html', $this -> getClassName());
        $this -> Template -> setFilePath('emoticons', $file_emoticons);

        $file_javascript = $this -> Weblication -> findJavascript('emoticon.js', $this -> getClassName(), true);
        $this -> Weblication -> getFrame() -> getHead() -> addJavaScript($file_javascript);
    }

    function prepare ()
    {
        $interfaces = $this -> Weblication -> getInterfaces();

        if (method_exists($this->Weblication->getMain(), 'setActiveMenueItem')) {
            $this -> Weblication -> getMain() -> setActiveMenueItem('News');
        }

        $dao_emoticons = DAO::createDAO($interfaces, $this -> Input -> getVar('tabledefine'));
        $result_emoticons = $dao_emoticons->getMultiple(null, null, [], array('sort' => 'ASC'));

        $maxcols = (int)$this -> Input -> getVar('maxcols');
        $maxrows = ceil($result_emoticons -> count() / $maxcols);

        $this -> Template -> setVar('maxcols', $maxcols);

        for ($r=0; $r < $maxrows; $r++) {

            $this -> Template -> newBlock('Row');
            for ($c=0; $c < $maxcols; $c++) {
                $this -> Template -> newBlock('Col');
                $this -> Template -> newBlock('Icon');
                $this -> Template -> setVar($result_emoticons -> getRow());
                $this -> Template -> setVar('jsident', addSlashes($result_emoticons -> getValue('ident')));
                $result_emoticons -> next();
            }

        }
    }

    function finalize(): string
    {
        $this -> Template -> parse($this -> Input -> getVar('emoticons'));
        return $this -> Template -> getContent($this -> Input -> getVar('emoticons'));
    }
}