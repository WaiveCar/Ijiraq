from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from datetime import datetime

app = Flask(__name__)
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:////var/db/waivescreen/main.db'
db = SQLAlchemy(app)

class ScreenCampaign(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  screen_id = db.Column(db.Integer)
  campaign_id = db.Column(db.Integer)
  created_at = db.Column(db.DateTime, default=datetime.utcnow)

class LocationHistory(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  job_id = db.Column(db.Integer)
  screen_id = db.Column(db.Integer)
  lat = db.Column(db.Float, default=None)
  lng = db.Column(db.Float, default=None)
  created_at = db.Column(db.DateTime, default=datetime.utcnow)

  def __repr__(self):
      return '<LocationHistory %r>' % self.id

SC = ScreenCampaign(screen_id=2, campaign_id=3)
db.session.add(SC)
db.session.commit()
