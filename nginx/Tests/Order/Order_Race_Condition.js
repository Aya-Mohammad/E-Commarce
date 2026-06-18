import http from 'k6/http';
import { check, sleep } from 'k6';
import { textSummary } from 'https://jslib.k6.io/k6-summary/0.0.2/index.js';

const tokensData = JSON.parse(open('../tokens.json'));

export const options = {
    scenarios: {
        race_condition: {
            executor: 'constant-vus',
            vus: 80,
            duration: '10s',
        },
    },
};

export default function () {
    const vuIndex = __VU - 1;
    const userToken = tokensData[vuIndex].token;

    const url = 'http://127.0.0.1/api/orders/place';
    
    const params = {
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${userToken}`,
            'X-Test-Type': 'order_race',
        },
    };

    const res = http.post(url, null, params);

    console.log(`User VU ${__VU} -> Status: ${res.status} | Response: ${res.body}`);

    check(res, {
        'Status is 201, 200 or 422/429': (r) => r.status === 200 || r.status === 201 || r.status === 422 || r.status === 429,
    });

    sleep(0.1);
}

export function handleSummary(data) {
    const isStrictNfr = __ENV.STRICT_NFR_MODE === 'true';

    const fileName = isStrictNfr
        ? '../Results/Order/Order_Race_Condition_After.json'
        : '../Results/Order/Order_Race_Condition_Before.json';

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