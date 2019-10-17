import os
import requests

config = {
    'sender': 'Waive <support@waive.com>',
    'transport_name': 'mailgun',
    'domain': 'waive.com',
    'api_key': '76d6847773f9514f75c80fd3ff4ec882-4167c382-b53ffbc6',
    'recipient': 'alex@waive.com'
  } if 'ENV' not in os.environ or os.environ['ENV'] != 'production' else {
    'sender': 'Waive <support@waive.com>',
    'transport_name': 'mailgun',
    'domain': 'waive.com',
    'api_key': '76d6847773f9514f75c80fd3ff4ec882-4167c382-b53ffbc6'
  }

def send_message(recpient):
  return requests.post(
    'https://api.mailgun.net/v3/{}/messages'.format(config['domain']),
    auth=("api", config['api_key']),
    data={
      'from': config['sender'],
      'to': [config['recipient'] if 'recipient' in config else recipient],
      'subject': 'Hello',
      'text': 'Testing some Mailgun awesomness!'
    }
  )
print(requests)
print(send_message('daleighan@gmail.com'));
