import http from 'k6/http';
import { check } from 'k6';
import { textSummary } from 'https://jslib.k6.io/k6-summary/0.0.2/index.js';

export const options = {
    scenarios: {
        login_stress_test: {
            executor: 'per-vu-iterations',  
            vus: 80,
            iterations: 1,                
            maxDuration: '2m',
        },
    },
};

const BASE_URL = 'http://127.0.0.1/api/auth/login';

export default function () {
    const phone    = String(963900000000 + __VU);
    const password = 'password';

    const payload = JSON.stringify({
        phone:    phone,
        password: password,
    });

    const params = {
        headers: {
            'Content-Type': 'application/json',
            'Accept':       'application/json',
            'X-Test-Type': 'auth_login',
        },
    };

    const res = http.post(BASE_URL, payload, params);

    console.log(`VU ${__VU} | phone=${phone} | status=${res.status}`);

    check(res, {
        'Login Success (200)': (r) => r.status === 200,
        'Valid Response': (r) => r.status === 200,
    });
}

export function handleSummary(data) {
    const isStrictNfr = __ENV.STRICT_NFR_MODE === 'true';

    const fileName = isStrictNfr
        ? '../Results/Auth/Login_Stress_After.json'
        : '../Results/Auth/Login_Stress_Before.json';

    const checks = data.metrics.checks;

    const passes = checks?.values?.passes || 0;
    const fails  = checks?.values?.fails || 0;

    const successRate =
        passes + fails > 0
            ? ((passes / (passes + fails)) * 100).toFixed(2)
            : '0.00';

    const failedRate =
        (data.metrics.http_req_failed.values.rate || 0) * 100;

    const totalRequests =
        data.metrics.http_reqs.values.count || 0;

    const failedRequests =
        Math.round(totalRequests * failedRate / 100);

    const stats = {
        total_requests: totalRequests,

        failed_requests: failedRequests,

        avg_response_time_ms:
            data.metrics.http_req_duration.values.avg,

        max_response_time_ms:
            data.metrics.http_req_duration.values.max,

        p95_response_time_ms:
            data.metrics.http_req_duration.values['p(95)'],

        success_rate: successRate,

        failed_rate: failedRate.toFixed(2),
    };

    return {
        [fileName]: JSON.stringify(stats, null, 4),
        stdout: textSummary(data, {
            indent: ' ',
            enableColors: true,
        }),
    };
}
