import {Duration} from "luxon";

export const displayDashIfBlank = (value) => {
    return value && String(value).length ? value : '-'
};
export const displayZeroIfBlank = (value) => {
    return value && String(value).length ? value : '0'
};

export const formatNumber = (number) => {
    return number && isFinite(number) ? Intl.NumberFormat().format(number) : number;
};

export const formatNumberDecimals = (number, decimals = 2) => {
    return number && isFinite(number) ? Intl.NumberFormat('en-US', { minimumFractionDigits: decimals, maximumFractionDigits: decimals }).format(number) : number;
};

export const formatSecondsToTime = (seconds) => {
    return seconds && isFinite(seconds) ? (
        parseInt(seconds) > 3600 ? Duration.fromObject({seconds: parseInt(seconds)}).toFormat('hh:mm:ss') : Duration.fromObject({seconds: parseInt(seconds)}).toFormat('mm:ss')
    ) : seconds;
};

