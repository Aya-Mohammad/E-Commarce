import http from 'k6/http';
import { check, sleep } from 'k6';
import { textSummary } from 'https://jslib.k6.io/k6-summary/0.0.2/index.js';

const SEARCH_TERMS = [
    'Hot',
    'Product',
    'Product 10',
    'Product 50',
    'Product 100',
];

export const options = {
    scenarios: {
        search_stress_test: {
            executor: 'constant-vus',
            vus: 150,
            duration: '1m',
        },
    },
};

export default function () {
    const baseUrl = 'http://127.0.0.1/api/search';
    const perPage = 15;
    let searchQuery;

    if (__VU === 150) {
        searchQuery = '%%%';          
    } else if (__VU === 149) {
        searchQuery = 'a'.repeat(90); 
    } else {
        const termIndex = (__VU - 1) % SEARCH_TERMS.length;
        searchQuery = SEARCH_TERMS[termIndex];
    }

    const finalUrl =
        `${baseUrl}?search=${encodeURIComponent(searchQuery)}&per_page=${perPage}`;

    const params = {
        headers: {
            'Accept': 'application/json',
            'X-Test-Type': 'search',
        },
    };

    const res = http.get(finalUrl, params);

    console.log(`VU ${__VU} | term="${searchQuery}" | status=${res.status}`);

    check(res, {
        'Valid Response': (r) => r.status === 200 || r.status === 422,
    });

    sleep(0.1);
}

export function handleSummary(data) {
    const isStrictNfr = __ENV.STRICT_NFR_MODE === 'true';

    const fileName = isStrictNfr
        ? '../Results/Search/Search_After.json'
        : '../Results/Search/Search_Before.json';

    const checks  = data.metrics.checks;
    const passes  = checks?.values?.passes || 0;
    const fails   = checks?.values?.fails  || 0;

    const successRate =
        passes + fails > 0
            ? ((passes / (passes + fails)) * 100).toFixed(2)
            : '0.00';

    const failedRate =
        (data.metrics.http_req_failed.values.rate || 0) * 100;

    const totalRequests  = data.metrics.http_reqs.values.count || 0;
    const failedRequests = Math.round(totalRequests * failedRate / 100);

    const stats = {
        total_requests:       totalRequests,
        failed_requests:      failedRequests,
        avg_response_time_ms: data.metrics.http_req_duration.values.avg,
        max_response_time_ms: data.metrics.http_req_duration.values.max,
        p95_response_time_ms: data.metrics.http_req_duration.values['p(95)'],
        success_rate:         successRate,
        failed_rate:          failedRate.toFixed(2),
    };

    return {
        [fileName]: JSON.stringify(stats, null, 4),
        stdout: textSummary(data, { indent: ' ', enableColors: true }),
    };
}