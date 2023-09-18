<?php
/**
 * -= Rapid Module Library (RML) =-
 * Stopwatch.class.php
 * Wie der Name schon sagt, handelt es sich hier um eine Stopuhr zum Messen von Laufzeiten etc.
 *
 * @version $Id: Stopwatch.class.php,v 1.1.1.1 2004/09/21 07:49:25 manhart Exp $
 * @version $Revision 1.0$
 * @version
 * @since 2003-07-10
 * @author Alexander Manhart <alexander@manhart.bayern>
 * @link https://alexander-manhart.de
 */

use pool\classes\Core\PoolObject;

# Defines for status:
const STOPWATCH_STOPPED = 0;    // Stopuhr laeuft nicht
const STOPWATCH_STARTED = 1;    // Stopuhr laeuft

/**
 * Stopwatch
 * Klasse Stopwatch zum Messen von Laufzeiten.
 *
 * @package rml
 * @author Alexander Manhart <alexander.manhart@freenet.de>
 * @version $Id: Stopwatch.class.php,v 1.1.1.1 2004/09/21 07:49:25 manhart Exp $
 * @access public
 **/
class Stopwatch extends PoolObject
{
    //@var array Holds all timer values
    //@access private
    var $times = [];

    //@var int For PHP4 function round
    //@access private
    var $precision = 5;

    /**
     * StopWatch::setPrecision()
     * Rundet auf "precision" Stellen auf oder ab.
     *
     * @access public
     * @param integer $value Precision
     **/
    function setPrecision($value = 5)
    {
        $this->precision = (int)$value;
    }

    /**
     * StopWatch::start()
     * Create a new timer entry in the array
     *
     * @access public
     * @param string $name Schluessel
     **/
    function start(string $name): self
    {
        $Diff = 0.0;
        if(isset($this->times[$name])) {
            if($this->times[$name][2] == STOPWATCH_STARTED) {
                return $this;
            }
            $Diff = $this->times[$name][1];
        }
        $this->times[$name] = [GetMicrotime(), (float)(0 + $Diff), STOPWATCH_STARTED];
        return $this;
    }

    /**
     * StopWatch::stop()
     * only stops when the timer is running.
     * multiple stopping does not change the elapsed time value
     * if the timer is not restarted the elapsed time is calculated here
     *
     * @access public
     * @param string $name Schluessel
     **/
    function stop($name): self
    {
        if($this->times[$name][2] == STOPWATCH_STARTED) {
            $Now = getMicrotime();
            $Diff = (float)($Now - $this->times[$name][0]);

            $this->times[$name][1] = $Diff;
            $this->times[$name][2] = STOPWATCH_STOPPED;
        }
        return $this;
    }

    /**
     * StopWatch::restart()
     * if you want to restart a timer after stopping
     * setting this value makes function stop() work again
     * start value remains unchanged
     *
     * @access public
     * @param $name
     **/
    function restart($name)
    {
        $this->times[$name][0] = getMicrotime();
        $this->times[$name][1] = 0.0;
        $this->times[$name][2] = STOPWATCH_STARTED;
    }

    /**
     * this function only returns the calculated runtime value
     *
     * @param string $name Schluessel
     * @return float Differenz
     */
    public function getDiff(string $name): float
    {
        $diff = 0.0;
        if($this->times[$name][2] === STOPWATCH_STOPPED) {
            $diff = $this->times[$name][1];
            $diff = round($diff, $this->precision);
        }
        return $diff;
    }
}