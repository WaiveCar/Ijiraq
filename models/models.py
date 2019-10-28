from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from datetime import datetime

app = Flask(__name__)
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:////var/db/waivescreen/main.db'
db = SQLAlchemy(app)

class Screen(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  uid = db.Column(db.Text, nullable=False)
  imei = db.Column(db.Text)
  phone = db.Column(db.Text)
  car = db.Column(db.Text)
  project = db.Column(db.Text)
  has_time = db.Column(db.Boolean, default=False)
  app_id = db.Column(db.Integer)
  ticker_id = db.Column(db.Integer)
  model = db.Column(db.Text)
  panels = db.Column(db.Text)
  photo = db.Column(db.Text)
  revenue = db.Column(db.Integer)
  impact = db.Column(db.Integer)
  lat = db.Column(db.Float, default=None)
  lng = db.Column(db.Float, default=None)
  location = db.Column(db.Text)
  version = db.Column(db.Text)
  version_time = db.Column(db.Integer)
  uptime = db.Column(db.Integer)
  pings = db.Column(db.Integer, default=0)
  port = db.Column(db.Integer)
  active = db.Column(db.Boolean, default=True)
  removed = db.Column(db.Boolean, default=False)
  is_fake = db.Column(db.Boolean, default=False)
  features = db.Column(db.Text)
  first_seen = db.Column(db.DateTime)
  last_task = db.Column(db.Integer, default=0)
  last_loc = db.Column(db.DateTime)
  last_seen = db.Column(db.DateTime)
  ignition_state = db.Column(db.Text)
  ignition_time = db.Column(db.DateTime)
  '''

  'place' => [
    'id'     => 'integer primary key autoincrement',
    'name'   => 'text not null',
    'lat'    => 'float default null',
    'lng'    => 'float default null',
    'radius' => 'float default null'
  ],
  '''
class Place(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  name = db.Column(db.Text, nullable=False)
  lat = db.Column(db.Float, default=None)
  lng = db.Column(db.Float, default=None)
  radius = db.Column(db.Float, default=None)


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

#SC = ScreenCampaign(screen_id=2, campaign_id=3)
#db.session.add(SC)
#db.session.commit()
found = Place.query.filter_by(id=1).all()

for each in found:
  print(each.__dict__)
