import {DateTime, Duration} from "luxon";

export function formatCurrency(num) {
    if (num === undefined || num === null || num.length === 0 || num === 0 || num === '0') {
        return '';
    }

    return new Intl.NumberFormat(`en-US`, {
        currency: `USD`,
        style: 'currency',
    }).format(num);
}

export function formatNumber(num) {
    if (num === undefined || num === null || num.length === 0) {
        return '';
    }

    return new Intl.NumberFormat(`en-US`).format(num);
}

export function formatSecondsAsHuman(num) {
    if (num === undefined || num === null || num.length === 0 || num === 0 || num === '0') {
        return '';
    }

    return Duration.fromObject({seconds: num}).toFormat('h:m:ss');
}
