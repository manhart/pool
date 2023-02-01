/*
 * POOL
 *
 * Error.class.js created at 27.01.23, 10:47
 *
 * @author Alexander Manhart <alexander@manhart-it.de>
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
    /**
     * Any Ajax response can contain custom errors in the JSON. An error type can be specified in the JSON Error.
     *
     * @param message
     * @param cause origin Error or null
     * @param serverSideType custom serverside error type
     */
    constructor(message, cause, serverSideType = '')
    {
        super(message, cause);
        this.serverSideType = serverSideType;
    }

    /**
     * custom serverside error
     * @return {string}
     */
    getServerSideType()
    {
        return this.serverSideType;
    }
}