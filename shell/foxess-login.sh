#!/bin/sh

curl -s -X POST \
-H 'User-Agent: Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.134 Safari/537.36 OPR/89.0.4447.83', \
-H 'Accept: application/json, text/plain, */*' \
-H 'lang: en' \
-H 'sec-ch-ua-platform: macOS' \
-H 'Sec-Fetch-Site: same-origin' \
-H 'Sec-Fetch-Mode: cors' \
-H 'Sec-Fetch-Dest: empty' \
-H 'Referer: https://www.foxesscloud.com/login?redirect=/' \
-H 'Accept-Language: en-US;q=0.9,en;q=0.8,de;q=0.7,nl;q=0.6' \
-H 'Connection: keep-alive' \
-H 'X-Requested-With: XMLHttpRequest' \
-H 'token: ' \
-d "{
    \"user\": \"${FOXESS_USERNAME}\",
    \"password\": \"${FOXESS_PASSWORD}\"
}" \
https://www.foxesscloud.com/c/v0/user/login |
jq -r '.result.token' > .token
