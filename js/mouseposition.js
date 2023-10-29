/*
 * This file is part of POOL (PHP Object-Oriented Library)
 *
 * (c) Alexander Manhart <alexander@manhart-it.de>
 *
 * For a list of contributors, please see the CONTRIBUTORS.md file
 * @see https://github.com/manhart/pool/blob/master/CONTRIBUTORS.md
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code, or visit the following link:
 * @see https://github.com/manhart/pool/blob/master/LICENSE
 *
 * For more information about this project:
 * @see https://github.com/manhart/pool
 */

// @todo: remove this file?

MousePosition = function() {
    this.mousePosY = 0;
this.mousePosX = 0;
}
MousePosition.prototype.getBody = function(e) {
    return (document.compatMode && document.compatMode != "BackCompat") ? document.documentElement : document.body;
}
MousePosition.prototype.detect = function(e) {
    mousePosY = (is.ns) ? (e.pageY) : (e.clientY + this.getBody().scrollTop);
    mousePosX = (is.ns) ? (e.pageX) : (e.clientX + this.getBody().scrollLeft);

    if (is.mac && is.ie5) {
        mousePosY += parseInt('0' + document.getTrueBody().currentStyle.marginTop, 10);
    }
    this.mousePosY = mousePosY;
    this.mousePosX = mousePosX;
}

MousePosition = new MousePosition();