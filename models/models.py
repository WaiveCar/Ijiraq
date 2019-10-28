from flask import Flask
from flask_sqlalchemy import SQLAlchemy
from datetime import datetime

app = Flask(__name__)
app.config['SQLALCHEMY_DATABASE_URI'] = 'sqlite:////var/db/waivescreen/main.db'
db = SQLAlchemy(app)

class Screen(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  # A uid self-reported by the screen (as of the writing
  # of this comment, using dmidecode to get the CPU ID)
  uid = db.Column(db.Text, nullable=False)
  # A human readable name
  serial = db.Column(db.Text)
  # If the device goes offline this will tell us
  # what it is that dissappeared so we can check
  last_campaign_id = db.Column(db.Integer)
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
  def __repr__(self):
      return '<Screen %r>' % self.id

class Place(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  name = db.Column(db.Text, nullable=False)
  lat = db.Column(db.Float, default=None)
  lng = db.Column(db.Float, default=None)
  radius = db.Column(db.Float, default=None)
  def __repr__(self):
      return '<Place %r>' % self.id

class Exclusive(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  set_id = db.Column(db.Integer)
  whitelist = db.Column(db.Boolean) #if true then this is inclusive, if false 
  campaign_id = db.Column(db.Integer) #then we should leave it out.
  def __repr__(self):
      return '<Exclusive %r>' % self.id

class RevenueHistory(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  screen_id = db.Column(db.Integer)
  revenue_total = db.Column(db.Integer) #deltas can be manually computed for now
  created_at = db.Column(db.DateTime, default=datetime.utcnow)
  def __repr__(self):
      return '<RevenueHistory %r>' % self.id

class Attribution(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  screen_id = db.Column(db.Integer)
  type = db.Column(db.Text) #such as wifi/plate, etc
  signal = db.Column(db.Integer) #optional, could be distance, RSSI
  mark = db.Column(db.Text) #such as the 48-bit MAC address
  created_at = db.Column(db.DateTime, default=datetime.utcnow)
  def __repr__(self):
      return '<Attribution %r>' % self.id

class Example(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  created_at = db.Column(db.DateTime, default=datetime.utcnow)
  def __repr__(self):
      return '<Example %r>' % self.id

class Organization(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  name = db.Column(db.Text)
  image = db.Column(db.Text)
  def __repr__(self):
      return '<Organization %r>' % self.id

class Brand(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  organization_id = db.Column(db.Integer)
  name = db.Column(db.Text)
  image = db.Column(db.Text)
  balance = db.Column(db.Integer)
  created_at = db.Column(db.DateTime, default=datetime.utcnow)
  def __repr__(self):
      return '<Brand %r>' % self.id

class ScreenCampaign(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  screen_id = db.Column(db.Integer)
  campaign_id = db.Column(db.Integer)
  created_at = db.Column(db.DateTime, default=datetime.utcnow)
  def __repr__(self):
      return '<ScreenCampaign %r>' % self.id

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
found = Brand.query.filter_by(id=1).all()

for each in found:
  print(each.__dict__)
