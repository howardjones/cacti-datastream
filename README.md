cacti-influx
============

A cacti plugin to export Cacti poller data into influxdb, graphite/carbon and generic RabbitMQ topic queues

All these new time-series tools that are so fashionable are great, and have nice large-scale tools
for dashboards and presentation, but are mostly geared around instrumenting applications. This plugin
lets you leverage the existing, working, Cacti poller to be able to have network or other data alongside
your app metrics. You can do it in a way that doesn't force the network folks to use your tool, too. 
Although putting together a quick comparison graph in Graphite is considerably nice than doing the same
job in Cacti.

