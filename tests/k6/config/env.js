export function toNumber(name, fallback) {
    const raw = __ENV[name];
    if (raw === undefined || raw === null || raw === '') {
        return fallback;
    }

    const parsed = Number(raw);
    return Number.isFinite(parsed) ? parsed : fallback;
}

export function toBoolean(name, fallback = false) {
    const raw = __ENV[name];
    if (raw === undefined || raw === null || raw === '') {
        return fallback;
    }

    return String(raw).toLowerCase() === 'true';
}

export const env = {
    baseUrl: __ENV.BASE_URL || 'http://localhost:8888',
    testDuration: __ENV.TEST_DURATION || '2m',
};
