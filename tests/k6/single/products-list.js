import http from 'k6/http';
import { check, sleep } from 'k6';

export let options = {
    stages: [
        { duration: '10s', target: 50 },
        { duration: '20s', target: 50 },
        { duration: '10s', target: 100 },
        { duration: '15s', target: 100 },
        { duration: '10s', target: 0 },
    ],
    thresholds: {
        http_req_failed: ['rate<0.05'],
        http_req_duration: ['p(95)<1000'],
    },
};

const BASE_URL = 'http://localhost';

export default function () {
    const url = `${BASE_URL}/api/products`;
    let res = http.get(url);

    check(res, {
        'status is 200': (r) => r.status === 200,
    });

    sleep(Math.random() * 1 + 1);
}
