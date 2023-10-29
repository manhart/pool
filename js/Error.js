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

class PoolError extends Error {
    /**
     * @param message error message
     * @param cause reference to the original error or null
     */
    constructor(message, cause = null)
    {
        super(message);
        this.name = this.constructor.name;
        this.cause = cause;
    }

    /**
     * return cause. reference to the original error
     * @return {unknown}
     */
    getCause()
    {
        return this.cause;
    }
}

class PoolAjaxResponseError extends PoolError {
    serverSideType = '';
    responseText = '';
    /**
     * Any Ajax response can contain custom errors in the JSON. An error type can be specified in the JSON Error.
     *
     * @param message
     * @param cause origin Error or null
     * @param serverSideType custom serverside error type
     * @param responseText
     */
    constructor(message, cause, serverSideType = '', responseText = '')
    {
        super(message, cause);
        this.serverSideType = serverSideType;
        this.responseText = responseText;
    }

    /**
     * custom serverside error
     * @return {string}
     */
    getServerSideType()
    {
        return this.serverSideType;
    }

    getResponseText()
    {
        return this.responseText;
    }
}

class PoolInvalidArgumentError extends PoolError {
}