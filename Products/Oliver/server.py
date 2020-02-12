#!/usr/bin/env python3
import logging
from flask import Flask, send_from_directory, render_template, request, jsonify
from requests import post
from services.payments import charge_for_notice
import json
import os
import random

ROOT = os.path.dirname(os.path.realpath(__file__))
app = Flask(__name__)
app.config['SEND_FILE_MAX_AGE_DEFAULT'] = 0

@app.route('/buy', methods=['GET','POST'])
def buy():

  logging.warning(list(request.files.keys()))
  dataPre = dict(request.form)
  dataPost = {}

  for k,v in dataPre.items():
    if v:
      dataPost[k] = v
      logging.warning("{} {}".format(k, v[:120]))

  data = dataPost
  data['goal_seconds'] = 200 * 7.5

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

    # Everything is flat priced right now
    amount = 399
    charge = charge_for_notice(
      data.get('email'), {
        'number': data.get('number'),
        'exp_month': data.get('expMonth'),
        'exp_year': data.get('expYear'),
        'cvv': data.get('cvv'),
      },
      '400',
      '123', #ad_id,
    )

    if charge['charge'].status != 'succeeded':
      logging.warning(charge)
      return jsonify({'res': False})

    data['user_id'] = charge['user'].id
    data['card_id'] = charge['card'].id
    data['charge_id'] = charge['charge'].id

    for i in ['number', 'expMonth', 'expYear', 'cvv']:
      del data[i]

    # canvasText is the message 
    # backgroundColor is an HSL
    # foregroundColor is an HSL
    # category
    # startDate
    # location is boost zone
    ad_id = post (
      'http://staging.waivescreen.com/api/campaign',
      data=data,
      files=request.files
    )

    # Todo: 
    #
    # (1) we need to make sure that the ad was
    #     successfully created before doing the processing
    #
    # (2) we need to notify human beings when this process
    #     has failed, with the contact information of
    #     the customer it has failed on so that we can
    #     contact them IMMEDIATELY to apologize and 
    #     correct the problem.
    #
    # (3) we need to *fully log* the post data so we
    #     can replay a failed creation in order to
    #     be able to diagnose the problem
    #
    # (4) We can do 2-stage payment processing
    #

    #receipt = send_receipt(data.get('email'), ad_id)
    logging.warning(ad_id)
    return jsonify({'res': True, 'ad_id': json.loads(ad_id.text)})
    
  except Exception as e:
    if hasattr(e, 'error'):
      return jsonify({'res': False, 'data': e.error})
    elif hasattr(e, 'response'):
      return jsonify({'res': False, 'data': e.response.reason, 'code': e.response.status_code})
    else:
      raise e

@app.route('/<path:path>')
def serve(path):

  if "notices/wizard" in path:
    return render_template("notices/wizard/index.html".format(ROOT))

  elif "v/" in path:
    return render_template("campaigns/show/index.html")

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
  app.config['TEMPLATES_AUTO_RELOAD'] = True
  app.run()
  #from waitress import serve
  #serve(app, host="0.0.0.0", port=5000)
