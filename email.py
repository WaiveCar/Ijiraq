import os

config = {
    'sender': 'Waive <support@waive.com>',
    'transport_name': 'mailgun',
    'transport': {
      'auth': {
        'domain': 'waive.com',
        'api_key': '76d6847773f9514f75c80fd3ff4ec882-4167c382-b53ffbc6'
      }
    }
  } if 'ENV' not in os.environ or os.environ['ENV'] != 'production' else {
    'sender': 'Waive <support@waive.com>',
    'transport_name': 'mailgun',
    'transport': {
      'auth': {
        'domain': 'waive.com',
        'api_key': '76d6847773f9514f75c80fd3ff4ec882-4167c382-b53ffbc6'
      }
    }
  }
