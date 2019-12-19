#!/usr/bin/env python3
import logging
from flask import Flask, send_from_directory, render_template, request, jsonify
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
    logging.warning(list(request.files.keys()))
    for k,v in data.items():
      logging.warning("{} {}".format(k, v[:20]))
    try:
      #
      # We don't want CC data posting to waivescreen at all, ever
      # We want to decrease the surface area of compromise and we
      # can't assume that waivescreen is secure. It *should* be and
      # compromising a screen *shouldn't* give secrets to get to 
      # waivescreen, but assuming that's true is stupid.
      #

      # The first thing we do is establish a user id
      # essentially keyed by the phone number or the email
      # 
      # If this is an email or phone number we've seen before
      # we'll "taint" the record as possibly the same person
      # so if something wacky happens we can discard the whole
      # group.
      #

      # canvasText is the message 
      # backgroundColor is an HSL
      # foregroundColor is an HSL
      # category
      # startDate
      # location is boost zone
      ad_id = post (
        'http://staging.waivescreen.com/api/campaign',
        data=data
      )
      logging.debug(ad_id.text)

      """
      # Everything is flat priced right now
      amount = 399
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
      charge = dict(charge)
      """
      charge = {}
      receipt = send_receipt(data.get('email'), ad_id)
      logging.warning(ad_id)
      return jsonify({'ad_id': ad_id.text})
      
      return jsonify({
          'ad_id': ad_id,
          'email': receipt
      })

    except Exception as e:
      logging.warning(e)
      if type(e).__name__ == 'CardError':
          return e.error, e.http_status
      else:
          return e.response.reason, e.response.status_code

@app.route('/<path:path>')
def serve(path):
  if "notices/wizard" in path:
    return render_template("notices/wizard/index.html".format(ROOT))

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
