#!/usr/bin/env python3
import logging
from flask import Flask, send_from_directory, render_template
import os
import random

ROOT = os.path.dirname(os.path.realpath(__file__))
app = Flask(__name__)
app.config['SEND_FILE_MAX_AGE_DEFAULT'] = 0

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
  app.config['TEMPLATES_AUTO_RELOAD'] = True
  app.run(host='0.0.0.0')
