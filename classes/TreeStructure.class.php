<?php
/**
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

/**
 * @version $Id: TreeStructure.class.php,v 1.5 2006/08/17 08:38:04 hoesl Exp $
 * @copyright 2003
 **/
class TreeStructure
{
    var $root = null;
    var $activeNode = null;

    /**
     * TreeStructure constructor.
     * @param TreeNode $TreeNode_root TreeNode
     * @param Input $Input Input
     */
    function __construct(&$TreeNode_root, &$Input) {
        $this -> root = $TreeNode_root;

        $nodeIdent = $Input -> getVar('nodeIdent');
        $this -> activeNode = $this -> findNode($this -> root, $nodeIdent);

        // falls kein nodeIdent �bergeben wurde
        if(!$this -> activeNode) {
            $this -> activeNode = &$TreeNode_root;
        }
    }

    function &findNode(&$node, $nodeIdent)
    {
        $identParts = explode('.', $nodeIdent);
        if(is_array($identParts) and sizeof($identParts)>0){
            if (is_a($node, 'TreeNode')) {
                for ($c=0; $c<$node -> countChilds(); $c++) {
                    $child = &$node -> getChild($c);
                    if (isset($identParts[$child -> getLevel()-1]) and ($child -> getIndex() == $identParts[$child -> getLevel()-1])) {
                        if(count($identParts) <= $child -> getLevel()) {
                            return $child;
                        }
                        else {
                            return $this -> findNode($child, $nodeIdent);
                        }
                    }
                }
                // check root
                if($node -> getIndex() == $identParts[$node -> getLevel()-1] and sizeof($identParts) == 1) {
                    return $node;
                }
            }
        }
        $result = null;
        return $result;
    }

    function &findNodeByCaption(&$node, $caption)
    {
        if (is_a($node, 'TreeNode')) {
            // check root
            if($node -> getCaption() == $caption) {
                return $node;
            }
            for ($c=0; $c<$node -> countChilds(); $c++) {
                $child = &$node -> getChild($c);
                if ($child -> getCaption() == $caption) {
                    return $child;
                }
                else {
                    $result = $this -> findNodeByCaption($child, $caption);
                    if(!is_null($result)) return $result;
                }
            }
        }
        return null;
    }

    function &findNodeByName(&$node, $name)
    {
        if (is_a($node, 'TreeNode')) {
            // check root
            if(strcmp($node -> getName(), $name) == 0) {
                return $node;
            }
            $countChilds=$node -> countChilds();
            for ($c=0; $c<$countChilds; $c++) {
                $child = &$node -> getChild($c);
                if (strcmp($child -> getName(), $name) == 0) {
                    return $child;
                }
                else {
                    $result = &$this -> findNodeByName($child, $name);
                    if(!is_null($result)) return $result;
                }
            }
        }
        return null;
    }

    function &getRoot()
    {
        return $this -> root;
    }

    function &getActiveNode()
    {
        return $this -> activeNode;
    }

}

class TreeNode
{
    var $caption = '';
    var $options = null;
    var $childs = array();
    var $parent = null;
    var $level = 1;
    var $index = 1;
    var $ident = 1;
    var $name = '';

    function __construct($caption, $properties=null, $options=null)
    {
        $this->caption = $caption;
        $this->options = $options;

        if(is_array($properties)) {
            foreach($properties as $property_key => $property_value) {
                $this -> $property_key = $property_value;
            }
        }
    }

    function getCaption()
    {
        return $this -> caption;
    }

    function getName()
    {
        return $this -> name;
    }

    function &addChild(&$TreeNode)
    {
        $TreeNode -> setParent($this);
        $this->childs[] = &$TreeNode;
        $TreeNode -> setIndex($this -> countChilds());
        return $TreeNode;
    }

    function countChilds()
    {
        return count($this -> childs);
    }

    function &getChild($index)
    {
        return $this -> childs[$index];
    }

    function setParent(&$TreeNode)
    {
        $this -> incLevel($TreeNode -> getLevel());
        $this -> parent = &$TreeNode;
    }

    function &getParent()
    {
        return $this -> parent;
    }

    function getLevel()
    {
        return $this -> level;
    }

    function incLevel($inc=1)
    {
        return $this -> level = $this -> level + $inc;
    }

    function getIndex()
    {
        return $this -> index;
    }

    function setIndex($index)
    {
        $this -> index = $index;
        if(is_a($this -> parent, 'TreeNode')) {
            $this -> ident = $this -> parent -> getIdent() . '.' . $this -> index;
        }
    }

    function getIdent()
    {
        return $this -> ident;
    }

    function getProperty($key)
    {
        return $this -> $key;
    }

    function getOptions($key=null)
    {
        if($key!=null){
            return $this -> options[$key];
        }
        else {
            return $this -> options;
        }
    }

    function setOptions($options)
    {
        $this -> options = $options;
    }
}