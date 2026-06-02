import http from 'k6/http';
import { check } from 'k6';
import { Counter, Rate } from 'k6/metrics';
import { env, toBoolean, toNumber } from '../config/env.js';

const MIN_RPS = 0.1;
const SAFE_MAX_RPS = 1.9;

const allowThrottle = toBoolean('ALLOW_THROTTLE', false);
const targetRpsInput = toNumber('TARGET_RPS', 1.5);
const timeUnitSeconds = Math.max(1, Math.round(toNumber('TIME_UNIT_SECONDS', 10)));
const preAllocatedVUs = Math.max(1, Math.round(toNumber('PRE_ALLOCATED_VUS', 5)));
const maxVUs = Math.max(preAllocatedVUs, Math.round(toNumber('MAX_VUS', 30)));

// products GET throttle is 120 requests/minute per actor key (roughly 2 RPS).
// In performance mode, keep default load below throttle to measure API performance.
// In throttle mode, allow higher RPS to validate rate limiter behavior.
const targetRps = allowThrottle
    ? Math.max(MIN_RPS, targetRpsInput)
    : Math.max(MIN_RPS, Math.min(SAFE_MAX_RPS, targetRpsInput));
const targetIterationsPerTimeUnit = Math.max(1, Math.round(targetRps * timeUnitSeconds));

const status200Count = new Counter('http_status_200_count');
const status429Count = new Counter('http_status_429_count');
const statusOtherCount = new Counter('http_status_other_count');

const status200Rate = new Rate('http_status_200_rate');
const status429Rate = new Rate('http_status_429_rate');

export let options = {
    scenarios: {
        products_perf: {
            executor: 'constant-arrival-rate',
            rate: targetIterationsPerTimeUnit,
            timeUnit: `${timeUnitSeconds}s`,
            duration: env.testDuration,
            preAllocatedVUs,
            maxVUs,
        },
    },
    thresholds: {
        http_req_failed: ['rate<0.01'],
        http_req_duration: ['p(95)<300', 'p(99)<500'],
        http_status_200_rate: ['rate>0.99'],
        ...(allowThrottle ? {} : { http_status_429_rate: ['rate<0.01'] }),
    },
};

export default function () {
    const url = `${env.baseUrl}/api/products`;
    let res = http.get(url);

    if (res.status === 200) {
        status200Count.add(1);
    } else if (res.status === 429) {
        status429Count.add(1);
    } else {
        statusOtherCount.add(1);
    }

    status200Rate.add(res.status === 200);
    status429Rate.add(res.status === 429);

    check(res, {
        'status is 200': (r) => r.status === 200,
    });
}
