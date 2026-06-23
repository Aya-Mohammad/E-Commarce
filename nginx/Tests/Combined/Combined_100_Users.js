import http from 'k6/http';
import { check, sleep, group } from 'k6';
import { textSummary } from 'https://jslib.k6.io/k6-summary/0.0.2/index.js';

export const options = {
    scenarios: {
        browsers: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '20s', target: 30 },
                { duration: '2m', target: 30 },
            ],
            exec: 'browserScenario',
            gracefulRampDown: '10s',
        },
        shoppers: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '20s', target: 50 },
                { duration: '2m', target: 50 },
            ],
            exec: 'shopperScenario',
            gracefulRampDown: '10s',
        },
        buyers: {
            executor: 'ramping-vus',
            startVUs: 0,
            stages: [
                { duration: '20s', target: 20 },
                { duration: '2m', target: 20 },
            ],
            exec: 'buyerScenario',
            gracefulRampDown: '10s',
        },
    },
};

const BASE_URL = 'http://127.0.0.1';

let cachedAuthHeaders = null;

function getAuthHeaders() {
    if (cachedAuthHeaders !== null) {
        return cachedAuthHeaders;
    }

    const phone = String(963900000000 + __VU);
    const res = http.post(
        `${BASE_URL}/api/auth/login`,
        JSON.stringify({ phone, password: 'password' }),
        {
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Test-Type': 'combined_100',
                'X-Operation': 'auth_login',
            },
        }
    );

    if (res.status !== 200) {
        console.log(`[Login Failed] VU:${__VU} | status:${res.status}`);
        return null;
    }

    const body = res.json();
    const token = body?.data?.access_token ?? body?.data?.token ?? body?.token;

    if (!token) {
        console.log(`[No Token] VU:${__VU} | body:${res.body}`);
        return null;
    }

    cachedAuthHeaders = {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': `Bearer ${token}`,
        'X-Test-Type': 'combined_100',
    };

    return cachedAuthHeaders;
}

function performSearch(base) {
    group('Search', function () {
        const res = http.get(
            `${BASE_URL}/api/search?search=Hot&per_page=15`,
            { headers: { ...base, 'X-Operation': 'search' } }
        );
        check(res, { 'Search OK': (r) => [200, 422].includes(r.status) });
    });
    sleep(0.3);
}

function showProduct(base) {
    const productId = ((__VU - 1) % 10) + 1;
    group('Show Product', function () {
        const res = http.get(
            `${BASE_URL}/api/products/${productId}`,
            { headers: { ...base, 'X-Operation': 'product_show' } }
        );
        check(res, { 'Show Product OK': (r) => [200, 404].includes(r.status) });
    });
    sleep(0.3);
}

function addToCart(base) {
    const productId = ((__VU - 1) % 10) + 1;
    group('Add to Cart', function () {
        const res = http.post(
            `${BASE_URL}/api/cart/add`,
            JSON.stringify({ product_id: productId, quantity: 1 }),
            { headers: { ...base, 'X-Operation': 'cart' } }
        );
        check(res, { 'Add Cart OK': (r) => [200, 422, 404].includes(r.status) });
    });
    sleep(0.3);
}

function placeOrder(base) {
    group('Place Order', function () {
        const res = http.post(
            `${BASE_URL}/api/orders/place`,
            null,
            { headers: { ...base, 'X-Operation': 'order' } }
        );
        check(res, { 'Place Order OK': (r) => [200, 201, 422, 429].includes(r.status) });
    });
    sleep(0.5);
}

export function browserScenario() {
    const headers = getAuthHeaders();
    if (!headers) { sleep(2); return; }

    performSearch(headers);
    showProduct(headers);
    sleep(0.5);
}

export function shopperScenario() {
    const headers = getAuthHeaders();
    if (!headers) { sleep(2); return; }

    performSearch(headers);
    showProduct(headers);
    addToCart(headers);
    sleep(0.5);
}

export function buyerScenario() {
    const headers = getAuthHeaders();
    if (!headers) { sleep(2); return; }

    performSearch(headers);
    showProduct(headers);
    addToCart(headers);
    placeOrder(headers);
    sleep(0.5);
}

export function handleSummary(data) {
    const isStrictNfr = __ENV.STRICT_NFR_MODE === 'true';

    const fileName = isStrictNfr
        ? '../Results/Combined/Combined_100_After.json'
        : '../Results/Combined/Combined_100_Before.json';

    const checks  = data.metrics.checks;
    const passes  = checks?.values?.passes || 0;
    const fails   = checks?.values?.fails  || 0;

    const successRate = passes + fails > 0
        ? ((passes / (passes + fails)) * 100).toFixed(2)
        : '0.00';

    const failedRate    = (data.metrics.http_req_failed?.values?.rate || 0) * 100;
    const totalRequests = data.metrics.http_reqs?.values?.count || 0;

    const stats = {
        vus: 100,
        scenarios: {
            browsers: 30,
            shoppers: 50,
            buyers:   20,
        },
        total_requests:       totalRequests,
        failed_requests:      Math.round(totalRequests * failedRate / 100),
        avg_response_time_ms: data.metrics.http_req_duration?.values?.avg  || 0,
        max_response_time_ms: data.metrics.http_req_duration?.values?.max  || 0,
        p95_response_time_ms: data.metrics.http_req_duration?.values['p(95)'] || 0,
        success_rate:         successRate,
        failed_rate:          failedRate.toFixed(2),
    };

    return {
        [fileName]: JSON.stringify(stats, null, 4),
        stdout: textSummary(data, { indent: ' ', enableColors: true }),
    };
}
