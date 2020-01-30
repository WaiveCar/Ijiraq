Here's the format:

line 1: sms text
line 2: email subject
line 3...eof: email content.

email is _header + file[3:...] + _footer

Variables defined:
  
  $campaign_link  : web link to view the campaign dashboard
  $name           : the given name of the person who bought it
  $play_count     : how many times the ad played (aggregate)
  $date_start     : human readable start of campaign
  $date_end       : human readable end of campaign

  $order          : Their full order record
  $user           : Their full user record
  $campaign       : Their full campaign record
