#!/usr/bin/env python3
import logging
from flask import Flask, send_from_directory, render_template, request
from requests import post
from services.payments import charge_for_notice
from services.email import send_receipt
import json
import os
import random

ROOT = os.path.dirname(os.path.realpath(__file__))
app = Flask(__name__)
app.config['SEND_FILE_MAX_AGE_DEFAULT'] = 0

@app.route('/buy', methods=['POST'])
def buy():
    data = request.form
    try:
        ad_id = post (
            'http://staging.waivescreen.com/api/campaign',
            data=data
        )
        charge = charge_for_notice(
           data.get('email'),
           {
                'card_number': data.get('number'),
                'exp_month': data.get('expMonth'),
                'exp_year': data.get('expYear'),
                'cvc': data.get('cvc'),

            },
           data.get('amount'),
           ad_id,
        )
        receipt = send_receipt(data.get('email'), ad_id)
        return {'ad_id': ad_id, 'charge': dict(charge), 'email': dict(receipt.json())}, 200
    except Exception as e:
        if type(e).__name__ == 'CardError':
            return e.error, e.http_status
        else:
            return e.response.reason, e.response.status_code

@app.route('/<path:path>')
def serve(path):
  if "campaigns/wizard" in path:
    return render_template("campaigns/wizard/index.html".format(ROOT))

  elif os.path.exists("{}/templates/{}/index.html".format(ROOT, path)):
    return render_template(path + '/index.html', rand=random.random())

  elif os.path.exists("{}/{}".format(ROOT, path)):
    return send_from_directory(ROOT, path)

  elif os.path.exists("{}/{}/index.html".format(ROOT, path)):
    return send_from_directory(ROOT, path + "/index.html")

  else:
    logging.warning("Can't find {}/{}".format(ROOT,path))
    return "not found"

@app.route('/')
def root():
  return serve('/')

if __name__ == '__main__':
  app.run()
