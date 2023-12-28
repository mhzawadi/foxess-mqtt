import hashlib
import time

import requests
from pprint import pprint


class GetAuthor:

    def get_signature(self, _token, _url, _lang='en'):
        timestamp = round(time.time() * 1000)
        time_sec = time.time()
        signature = fr'{_url}\r\n{_token}\r\n{timestamp}'
        print("fox time:", timestamp)
        print("python time:", time_sec)

        result = {
            'token': _token,
            'lang': _lang,
            'timestamp': str(timestamp),
            'signature': self.md5c(text=signature),
            'User-Agent': 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/117.0.0.0 Safari/537.36'
        }
        return result

    @staticmethod
    def md5c(text="", _type="lower"):
        res = hashlib.md5(text.encode(encoding='UTF-8')).hexdigest()
        if _type.__eq__("lower"):
            return res
        else:
            return res.upper()


domain = 'https://www.foxesscloud.com'
url = '/op/v0/device'
request_param = {"variables": ["generationPower"]}

key = '************************************'
headers = GetAuthor().get_signature(_token=key, _url=url)

# response = requests.post(url=domain + url, json=request_param, headers=headers)
#
# pprint(f'status_code:{response.status_code}')
# pprint(f'content:{response.content}')
