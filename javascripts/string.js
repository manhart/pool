/*
 * POOL
 *
 * string.js created at 09.03.21, 16:25
 *
 * @author A.Manhart <A.Manhart@group-7.de>
 * @copyright Copyright (c) 2021, GROUP7 AG
 */

/**
 * interpolate template-literals
 *
 * @param params
 * @returns {*}
 */
String.prototype.interpolate = function(params)
{
    const keys = Object.keys(params);
    const values = Object.values(params);
    return new Function(...keys, `return \`${this}\`;`)(...values);
}

/**
 * replace default placeholders e.g. {placeholder}
 *
 * @see https://stackoverflow.com/questions/7975005/format-a-javascript-string-using-placeholders-and-an-object-of-substitutions
 * @param array params
 * @returns {*}
 */
String.prototype.replaceholder = function(params)
{
    return this.replace(/{(\w+)}/g, (placeholderWithBraces, placeholder) =>
        params.hasOwnProperty(placeholder) ? params[placeholder] : placeholderWithBraces);
}