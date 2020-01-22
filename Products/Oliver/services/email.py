import os
import requests
from urllib.error import HTTPError
from flask import render_template

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

def parser(which, user):
  # first render the template
  rendered = {}
  for what in ['_header', '_footer', which]:
    rendered[what] = render_template('templates/email/{}.html'.format(what), user=user)

  return { 
    'sms': rendered[which][0],
    'subject': rendered[which][1],
    'email': rendered['_header'] + rendered[which][2:] + rendered['_footer']
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


