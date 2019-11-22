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

def send_message(recipient, subject, body):
  try:
    return requests.post(
      'https://api.mailgun.net/v3/{}/messages'.format(config['domain']),
      auth=("api", config['api_key']),
      data={
        'from': config['sender'],
        'to': [config['recipient'] if 'recipient' in config else recipient],
        'subject': subject,
        'html': body
      }
    )
  except Exception as e:
      raise e

def send_receipt(recipient, ad_id):
  return send_message(
      recipient, 
      'Thanks For Your Oliver Purchase!', 
      """
        <div>
          Ad: {}
          Content to be added to email later
        </div>
      """.format(ad_id)
  )

def send_approval(recipient, ad_id):
  return send_message(
      recipient, 
      'Your Oliver Posting Has Been Approved!', 
      """
        <div>
          Ad: {}
          Content to be added to email later
        </div>
      """.format(ad_id)
  )

def send_rejection(recipient, ad_id):
  return send_message(
      recipient, 
      'Your Oliver Posting Has Been Rejected.', 
      """
        <div>
          Ad: {}
          Content to be added to email later
        </div>
      """.format(ad_id)
  )

