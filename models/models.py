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

class Social(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  brand_id = db.Column(db.Integer)
  service = db.Column(db.Text)
  name = db.Column(db.Text)
  token = db.Column(db.Text)
  created_at = db.Column(db.DateTime, default=datetime.utcnow)
  def __repr__(self):
      return '<Social %r>' % self.id

class Contact(db.Model):
  # there's a more generic way to do this that
  #  we should totally implement when the time comes.
  id = db.Column(db.Integer, primary_key=True)
  name = db.Column(db.Text)
  twitter = db.Column(db.Text)
  instagram = db.Column(db.Text)
  facebook = db.Column(db.Text)
  email = db.Column(db.Text)
  website = db.Column(db.Text)
  phone = db.Column(db.Text)
  location = db.Column(db.Text)
  lat = db.Column(db.Float, default=None)
  lng = db.Column(db.Float, default=None)
  def __repr__(self):
      return '<Contact %r>' % self.id

class User(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  name = db.Column(db.Text)
  password = db.Column(db.Text)
  image = db.Column(db.Text)
  contact_id = db.Column(db.Integer)
  auto_approve = db.Column(db.Boolean, default=False)
  title = db.Column(db.Text)
  organization_id = db.Column(db.Integer)
  brand_id = db.Column(db.Integer)
  role = db.Column(db.Text) #either admin/manager/viewer
  created_at = db.Column(db.DateTime, default=datetime.utcnow)
  def __repr__(self):
      return '<User %r>' % self.id

class Widget(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  name = db.Column(db.Text) #what to call it
  image = db.Column(db.Text) #url of logo or screenshot
  type = db.Column(db.Text) #ticker or app
  topic = db.Column(db.Text) #optional, such as weather
  source = db.Column(db.Text) #The url where to get things
  created_at = db.Column(db.DateTime, default=datetime.utcnow)
  def __repr__(self):
      return '<Widget %r>' % self.id

class Campaign(db.Model):
  '''
  consider: potentially create a second table for "staging" campaigns
  that aren't active as opposed to relying on a boolean
  in this table below
  
  The start_minute and end_minute are for campaigns that 
  don't run 24 hours a day.
  
  The start_time and end_time are the bounds to do the 
  campaign. It doesn't need to be exactly timebound by
  these and can bleed over in either direction if it 
  gets to that.
  
  If they are empty, then it means that it's 24 hours a day
  '''
  id = db.Column(db.Integer, primary_key=True)
  title = db.Column(db.Text)
  ref_id = db.Column(db.Text)
  contact_id = db.Column(db.Integer)
  brand_id = db.Column(db.Integer)
  organization_id = db.Column(db.Integer)
  order_id = db.Column(db.Integer)
  asset = db.Column(db.Text)
  duration_seconds = db.Column(db.Integer)
  completed_seconds = db.Column(db.Integer, default=0)
  project = db.Column(db.Text, default='dev')
  '''
  This is a cheap classification system
  for the Oliver project. It'll probably
  change.
  '''
  topic = db.Column(db.Text)
  '''
  For now, until we get a geo db system
  this makes things easily queriable
  
  Stuff will be duplicated into shapelists
  '''
  lat = db.Column(db.Float, default=None)
  lng = db.Column(db.Float, default=None)
  radius = db.Column(db.Float, default=None)

  '''
  shape_list := [ polygon | circle ]* 
  polygon   := [ "Polygon", [ coord, ... ] ]
  circle    := [ "Circle", coord, radius ]
  coord     := [ lon, lat ]
  radius    := integer (meters)
  '''
  shape_list = db.Column(db.Text)
  start_minute = db.Column(db.Integer, default=None)
  end_minute = db.Column(db.Integer, default=None)
  is_active = db.Column(db.Boolean, default=False)
  is_approved = db.Column(db.Boolean, default=False)
  is_default = db.Column(db.Boolean, default=False)
  priority = db.Column(db.Integer, default=0)
  impression_count = db.Column(db.Integer)
  start_time = db.Column(db.DateTime, default=datetime.utcnow)
  end_time = db.Column(db.DateTime)
  def __repr__(self):
      return '<Campaign %r>' % self.id

class Tag(db.Model):
  '''
  In the future we can have different tag classes or namespaces
  But for the time being we just need 1 separation: LA and NY
  and that's literally it. Generalizability can come later.
  
  This is a list of tags, it's notable that we aren't really
  doing some kind of "normalization" like all the proper kids
  do because we don't want to be doing stupid table joins 
  everywhere to save a couple bytes.
  '''
  id = db.Column(db.Integer, primary_key=True)
  name = db.Column(db.Text, nullable=False)
  created_at = db.Column(db.DateTime, default=datetime.utcnow)
  def __repr__(self):
      return '<Tag %r>' % self.id

class ScreenTag(db.Model):
  '''
  #47 - the screen_id/tag is the unique constraint. There's
  probably a nice way to do it. Also if you really are doing
  things well then you use the whitelist from the tag table
  before inserting since we are keeping it daringly free-form
  '''
  id = db.Column(db.Integer, primary_key=True)
  screen_id = db.Column(db.Integer)
  tag = db.Column(db.Text)
  created_at = db.Column(db.DateTime, default=datetime.utcnow)
  def __repr__(self):
      return '<ScreenTag %r>' % self.id

class TagInfo(db.Model):
  '''
  #95 If different tags need different default campaign ids 
  or split kingdoms we do that here. It's basically a
  key/value with a name-space. Right now we don't have 
  a list of tags, probably should so that the screen_tag
  and tag_info table references a tag_list but this is
  fine for now.
  '''
  id = db.Column(db.Integer, primary_key=True)
  tag = db.Column(db.Text, nullable=False)
  key = db.Column(db.Text)
  value = db.Column(db.Text)
  created_at = db.Column(db.DateTime, default=datetime.utcnow)
  def __repr__(self):
      return '<TagInfo %r>' % self.id

class Task(db.Model):
  '''
  #107 - scoped tasks
  The id here is the referential id so that we 
  can group the responses
  '''
  id = db.Column(db.Integer, primary_key=True)
  created_at = db.Column(db.DateTime, default=datetime.utcnow)
  expiry_sec = db.Column(db.Integer, default=172800)
  scope = db.Column(db.Text)
  command = db.Column(db.Text)
  args = db.Column(db.Text)
  def __repr__(self):
      return '<Task %r>' % self.id

class TaskScreen(db.Model):
  # #39
  id = db.Column(db.Integer, primary_key=True)
  task_id = db.Column(db.Integer)
  screen_id = db.Column(db.Integer)
  def __repr__(self):
      return '<TaskScreen %r>' % self.id

class TaskResponse(db.Model):
  id = db.Column(db.Integer, primary_key=True)
  task_id = db.Column(db.Integer)
  screen_id = db.Column(db.Integer)
  response = db.Column(db.Text)
  created_at = db.Column(db.DateTime, default=datetime.utcnow)
  def __repr__(self):
      return '<TaskResponse %r>' % self.id

class ScreenHistory(db.Model):
  # #143
  id = db.Column(db.Integer, primary_key=True)
  screen_id = db.Column(db.Integer)
  action = db.Column(db.Text)
  value = db.Column(db.Text)
  old = db.Column(db.Text)
  created_at = db.Column(db.DateTime, default=datetime.utcnow)
  def __repr__(self):
      return '<ScreenHistory %r>' % self.id

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
found = ScreenHistory.query.filter_by(id=1).all()

for each in found:
  print(each.__dict__)
