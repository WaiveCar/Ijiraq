import os
import requests
from urllib.error import HTTPError

config = {
    'sender': 'Waive <support@waive.com>',
    'transport_name': 'mailgun',
    'domain': 'waive.com',
    'api_key': 'key-2804ba511f20c47a3c2dedcd36e87c92',
    'recipient': 'alex@waive.com'
  } if 'ENV' not in os.environ or os.environ['ENV'] != 'production' else {
    'sender': 'Waive <support@waive.com>',
    'transport_name': 'mailgun',
    'domain': 'waive.com',
    'api_key': 'key-2804ba511f20c47a3c2dedcd36e87c92'
  }

def send_message(recipient, subject, body):
  response = requests.post(
    'https://api.mailgun.net/v3/{}/messages'.format(config['domain']),
    auth=("api", config['api_key']),
    data={
      'from': config['sender'],
      'to': [config['recipient'] if 'recipient' in config else recipient],
      'subject': subject,
      'html': body
    }
  )
  try:
    response.raise_for_status()
  except requests.exceptions.HTTPError as e: 
      raise e
  return response

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

