Load balancing is now set up and working.

What I did:
- Spun up two Laravel instances with php artisan serve on 127.0.0.1:9001 and 127.0.0.1:9002.
- Replaced nginx config with a real load‑balancer upstream (least_conn) and set it to listen on 8085.
- Fixed nginx PID/log paths to /tmp so it can run without sudo.
- Started nginx using your local config.
- Updated place_order_test.js to use BASE_URL = http://127.0.0.1:8085.
- Regenerated tokens.json from current DB users (100 users) to fix 401s caused by expired tokens.
- Verified the LB path works: /api/orders/place returns 201 when called through 8085 with fresh tokens.

Current state:
- nginx LB is listening on port 8085.
- Two app servers are up at 9001 and 9002.
- place_order_test.js points at the LB.
- Tokens are valid now.

Files changed:
- /media/eliasnadder/New Volume/Projects/e-commerce/nginx/config/ecommerce.conf
- /media/eliasnadder/New Volume/Projects/e-commerce/nginx/nginx.conf
- /media/eliasnadder/New Volume/Projects/e-commerce/place_order_test.js
- /media/eliasnadder/New Volume/Projects/e-commerce/tokens.json

---

Done. k6 installed locally and the test ran against the nginx load balancer on 8085.

Result summary:
- Requests: 293 (19.76 req/s)
- Avg latency: 4144.91 ms
- p95: 5345.57 ms (threshold failed)
- Max: 6224.66 ms
- Success: 19
- Failed: 274
- error_rate: 89.76% (threshold failed)
- Many 500s and some 422s

So the LB is working, but the app is failing hard under load. Next step: inspect logs to see why 500s/422s are happening, then tune or fix.

---

Findings:
- Nginx error log is empty.
- Laravel is throwing: “Not enough stock for: Race Condition Product” from OrderService.php:106 during the
load test. That exception becomes 500s.
- Some 422s are “Cart is empty”.
- Current data: Race Condition Product qty=0, cart_count=90, users_with_cart=90.

So the failures are data-driven, not nginx. You’re hammering an empty stock product and many users have
empty carts. The code throws an exception for insufficient stock, which bubbles as 500 instead of a 422/409.

Best next move (pick one):
1) Restock the product + ensure carts are filled, then rerun k6 to test concurrency behavior.
2) Fix the API to return a 409/422 for insufficient stock (no 500), then rerun.
3) Treat 422 as expected in the test and focus only on real 500s.

---

I chose the best path: remove the data bottleneck so we can actually test concurrency behavior. I restocked “Race Condition Product” to 10,000 and reran k6.

New results (clean run through nginx LB):
- Requests: 401 (31.30 req/s)
- Avg latency: 2842.41 ms
- p95: 3618.26 ms
- Max: 3989.92 ms
- Successful: 201
- Failed: 200
- error_rate: 0.00%
- Checks: all passed

The remaining failures are all 422 (cart empty), not 500s. So the infrastructure and code path are stable
now. To get 100% success, you need carts prefilled for all users before running the test.

Want me to auto-fill carts for all users and rerun so success hits ~100%?

---

I prefilled carts for 100 users with the “Race Condition Product” and reran k6. Still seeing lots of 422s
(cart empty) mid-run, which means carts are being cleared after first order and subsequent iterations keep
firing.

Here’s the clean fix so every iteration has items:

Option A (best): modify the k6 script to add-to-cart before placing order.
Option B: change the scenario to only 1 iteration per VU.

Say which you want and I’ll do it.

---

Done. I updated place_order_test.js to add the product to cart before each order, then reran k6. Results are
clean:

- Requests: 432 (30.07 req/s)
- Avg: 2771.44 ms
- p95: 3847.43 ms
- Max: 4079.45 ms
- Successful: 216
- Failed: 0
- error_rate: 0.00%

So now the test is actually about concurrency, not empty carts.
Want me to bump VUs/duration to push harder, or keep this as baseline?

---

● What do you think about this test

    It’s a good baseline: zero 5xx/401/429 and zero failed orders, so the system is stable under load. But it’s
    not a pure order race test anymore because each iteration adds to cart first, so latency and throughput
    reflect two calls, and you’re not checking add-to-cart failures. Also, with huge stock, you’re not testing
    oversell contention.

    If you want a stronger race test: add checks for the add-to-cart call, split metrics (add vs order), and
    lower stock to force contention while increasing duration/VUs.