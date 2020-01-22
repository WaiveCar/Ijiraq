#!/usr/bin/python3
import requests
import os
import time

# LA County - We're pulling data from ZIMAS (Zone Information and Map Access System).  http://zimas.lacity.org/
# They provide a layer (B_SCHOOLS) with "Adult Entertainment" sublayers showing schools/parks and a 500 foot boundary around them.
# The property lines are on layer 10 and the 500 foot boundaries are on layer 11.
# They have different PolyStyle.color values, which could be used for filtering.
# The REST API is documented at: http://zimas.lacity.org/arcgis/rest/services/B_SCHOOLS/MapServer
LAC_URL = "http://zimas.lacity.org/arcgis/services/B_SCHOOLS/MapServer/KmlServer?Composite=false&LayerIDs=11,10&bbox={},{},{},{}&bboxSR=426&imageSR=4326"

# Rough encapsulation of LA County
# Format: Xmin, Ymin, Xmax, Ymax
LAC_BBOX = ( -118.67, 33.69, -118.140, 34.37 )


def split_bbox(bbox, sections):
  """ Split the boundaries for our query into smaller chunks.  The data returned depends on a "zoom level".
      There may also be limits to the number of elements returned.  So, we split up the requests to make
      sure we get results. """
  new_bboxes = []
  xinc = ( bbox[2] - bbox[0] ) / sections
  yinc = ( bbox[3] - bbox[1] ) / sections
  y = bbox[1]
  for i in range(sections):
    x = bbox[0]
    for j in range(sections):
      new_bboxes.append( (x, y, x + xinc, y + yinc) )
      x += xinc
    y += yinc
  return new_bboxes


def dl_restricted_zones(url, args, filename):
  r = requests.get(url.format(*args))
  if r.status_code == 200:
    with open(filename, 'wb') as f:
      f.write(r.content)
  else:
    print("Unable to download zones for {}. Server returned: {}".format(filename, r.status_code))


def lac_download(directory):
  bboxes = split_bbox(LAC_BBOX, 5)
  for i in range(len(bboxes)):
    dl_restricted_zones(LAC_URL, bboxes[i], '{}/lac_restricted_zones-{:02d}.kmz'.format(directory, i+1))


dest_dir = '/tmp/restricted_zones-{}'.format(time.strftime('%Y%m%d_%H%M%S'))
os.mkdir(dest_dir)
print("Saving downloaded files to: {}".format(dest_dir))
lac_download(dest_dir)
