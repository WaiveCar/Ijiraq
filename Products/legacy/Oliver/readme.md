Note: see [dooh.rst](dooh.rst) for an overview of the traditional approach and why it doesn't work.

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
points. Also after the API is "enabled" it takes a backseat. I don't really hear from it when it's on so if I decide that either I
don't want the service any more or I want to exclude a specific post, there's no clear way to do this.

If the theory is that expanding the reach increases lead generation and then servicing of the leads is the most important thing,
then this works. However

 1. blanket cross-posting dilutes quality and seems more impersonal
 2. thus there is a higher volume of poorer quality leads and a lower volume of higher quality ones

The complaint I see is usually "more leads, fewer sales". This approach doesn't come with "sensible defaults" or what in SW design is

"make easy things easy, hard things possible"

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
3. Output Engine: Outputs are sent to their CRUD/email places 


Value 
---

This leads to a separation of the platform and the media which addresses all the problems above. We don't care because
it solves the oversubscriber problem and we still get the money. The objective is to provide a kind of value where part
of the product is seen as "free", this is only possible if there's an intrinsic abstract value where the user values the 
system regardless of the material realities.

This is important. If an airliner served complimentary michelin star meals, people could arguably take a $150 flight just
for the food, considering the airplane ride as "free" since michelin meals can easily cost $150.  Someone else could consider
the michelin meal as "free" for the opposite reason.

A truly compelling product offering will contain some components which part of the target market will mistakenly call "free" because
the product goes beyond the product's expected offerings.

System
---

This initial exploration is a sophisticated model which may be parred down:

- Screens have physical size and resolutions/ratios (see notes)
- Layouts have Components and compatible Screens <-- user-surfaced
- Components have specifications

- User has many Services
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
 * Most DSP "supplier sides" have a homogeneous resolution as a function of necessity because the asset provided wasn't composite like we are intending.


FE design
---

It will be a permutation of the Aviv design. Effectively the following things have to be met:
 
 * what the user is getting should be prominantly featured during the process
 * zapier style search for more things to connect to
 * ^ the user should be given the opportunity to upgrade the experience

"the more you add, the better it is"

Layout Engine
---

We're trying to re-invent *as little* as possible. The idea is there is say regular HTML/CSS and then by annotating some dom nodes, say by adding a class or an HTML5 `data-*` attribute we can identify what "object type" a particular dom node is.

Then *probably* the rest of the stuff can be put into a URL structure.  For instance:

    /layouts/<layout id>/<campaign id>

Where `<campaign id>` will return "discrete" content from an API, such as an image or block of text along with say, a UUID.
So in the Statement of Work, it could report `campaign id/uuid` to specify the exact combination of stuff that was shown. The UUID
isn't necessarily a uuidv5 but it is someway to identify the actual assets that were displayed.


Output Engine
---

There has to be a format pipeline that can take html/css/js and then generate static images, mp4s, pdf etc... for different sources - hopefully with appropriate SVG/raster and DPI separation. This can be a completely separate, generic thing - it might already exist.

For now (2020-03) we are just going to focus on the html output


1.0
---

The point of the 1.0 is to have a flexible framework where the BL can be pivoted over the lifecycle of the customer discovery and validation (see steve blank).  The previous instance was a one-off instagram running on the screen architecture. We need a better system of creation

