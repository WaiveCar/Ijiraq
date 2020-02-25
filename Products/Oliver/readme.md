Pitch
---

For small businesses dissatisfied with the costs and expertise needed for social media and marketing management, oliver is a content amplifier that repackages existing presence as new content. Unlike buffer or hootsuite, oliver doesn't require anyone to steer the ship.

Background
---

There's a few major kinds of ads:

 * Introduction: Before a sale, before first buy
 * Reminder: Before a sale, to steal customers ("we try harder")
 * Reminder: After a sale, for referrals or return business.

With all it should feel personal.

Business owners want a known output for a known input. The output can be a set of odds so long
as it's not odds of odds ... that is to say it can be 1 in 10, but not a 1 in 10 of being 1 in 10
and a 2 in 10 of being a 3 in 10 and so on.

Convergence is the *only* important number. If a method irritates 80% but has a 10% convergence and that's the best, then that method is the one to do.

Current problems
---

Small businesses are preyed on by services and consultants:
  SEO Bots leeches SMM and scams

They want tools, not solutions, not consultants, not services.
Shouldn't pretend to know an answer or to care or to listen, instead, be a hammer.

They hate yelp because the business model converts that tool into a hustle

Success is tied too much to a specific service such as instagram. They don't like the reliance.

There's a "starve the beast" system. For google, each company in a market needs to stay in, at a loss, because if they pull out they
will lose more then if they stay in because it changes the pricing for the competitors and eventually customers will atrophy
and switch so they end up staying in as a form of necessity ... like some mandatory tax to do business.

Different systems work for different types of businesses. Followers don't always lead to sales.

Business owners don't mind if their BUSINESS is on social media, just that they don't want to necessarily personally be.

Social media management produces terrible results

Schedulers

 * They suck because most content looks canned and unengaging
 * It's a valuable abstraction but not to facilitate repetitive laziness

FB isn't targetted, focused, or streamlined enough for friction-free business interactions.

Purpose
---

Essentially an advertising transcoder

Give businesses an ability to take their content and go elsewhere.

The tool becomes a middleman instead of an endpoint but is still part of the flow.

The objective is a virtuous cycle feedback machine that can generate clients + testimonials.


Similar efforts
---

At the advent of "web 2.0", the term "mashup" was developed to describe stringing APIs together.  Yahoo and Google Pipes, EMML, and
a number of other efforts came out of this. 

The survivors are Zapier and IFTTT, both of which are more "smart home" and IoT focused these days.

The theory of why these didn't work as SMM alternatives is because 

 1. It wasn't their target market
 2. they don't seem to have a way to do curation, regulation, or mutation between the systems.

For instance, you just "enable" "Take X and blanket cross-post to Y" and then use some kind of funnel system to centralize contact
points.

If the theory is that expanding the reach increases lead generation and then servicing of the leads is the most important thing,
then this works. However

 1. blanket cross-posting dilutes quality and seems more impersonal
 2. thus there is a higher volume of poorer quality leads and a lower volume of higher quality ones

The complaint I see is usually "more leads, fewer sales". This approach doesn't come with "sensible defaults"

Product
---

Adaptive layouts for different mediums (resolution, aspect ratio)

Flow:

    Various inputs -> dynamic converters -> various outputs


The output system is some CRUD

The output can FEED BACK into the input. Since it's a single system that
monitors this process, it can be protected against feedback loops. This allows
creation and correction of messaging.

Details
---

Internally we should have different classes of objects, types, and satisfiability for them.

This will surface as different services

For instance

 * image   4x3 1x1 3x4 9x16 16x9 
 * video   ''
 * vector  ''
 * text    word and line count

Then there's the purpose of each

  logo, phone, email, social media, address, hours, etc.

Then things are either "live" or not.

For a user what they see is a set of layout/requirements along with satisfiability needs.

The system is effectively a "meat grinder":

respiration cycle:

1. Inputs are scanned.
2. Layouts are generated.
   a. Notifications are sent to the user
3. Outputs are sent to their CRUD/email places


Value 
---

This leads to a separation of the platform and the media which addresses all the problems above. We don't care because
it solves the oversubscriber problem and we still get the money. The objective is to provide a kind of value where part
of the product is seen as "free", this is only possible if there's an intrinsic abstract value where the user values the 
system regardless of the material realities.


System
---

This initial exploration is a sophisticated model which may be parred down:

- Screens have physical size and resolutions/ratios
- Layouts have Components and compatible Screens <-- user-surfaced
- Components have specifications

- Services have Assets with specifications. <-- user-surfaced
- a Layout is satisfied when its Components specifications can all be matched by available Services.
- a Screen is accessible when a compatible Layout is available.

The Layouts can get Assets for their Components the following ways:

  1. User-provided - They upload it/create it
  2. Asset-specific - A specific Asset from pre-existing digital sources is used (such as an existing instagram image)
  3. Condition-specific - This could be something like "most recent instagram upload" or "5 star review" etc.
     Note: condition specific content can either go on "auto-pilot" or can be reviewed/approved.

Notes: 

 * The Layout can have mixes for their Components. For example, some Components can be user provided while other can be asset-specific.
 * If content changes the actual instance of what showed where when needs to be saved, not just the notion of a stream.
 * This isn't necessarily surfaced to the user initially, they can see an overview representing it as "condition specific" Component. They can do
   a "deeper dive" and see more.
