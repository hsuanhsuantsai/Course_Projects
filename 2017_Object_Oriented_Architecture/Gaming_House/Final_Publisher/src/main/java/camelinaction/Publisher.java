package camelinaction;

import org.apache.camel.CamelContext;
import org.apache.camel.builder.RouteBuilder;
import org.apache.camel.impl.DefaultCamelContext;

import javax.jms.ConnectionFactory;

import org.apache.activemq.ActiveMQConnectionFactory;
import org.apache.camel.component.jms.JmsComponent;

public class Publisher {

    public static void main(String args[]) throws Exception {
        // create CamelContext
        CamelContext context = new DefaultCamelContext();

        // connect to ActiveMQ JMS broker listening on localhost on port 61616
        ConnectionFactory connectionFactory = new ActiveMQConnectionFactory("tcp://localhost:61616");
        context.addComponent("jms", JmsComponent.jmsComponentAutoAcknowledge(connectionFactory));
      
        // add our route to the CamelContext
        context.addRoutes(new RouteBuilder() {
            public void configure() {	
            	from("jms:Results").to("jms:topic:Results_Topic");
            }
        });

        while(true) {
	        // start the route and let it do its work
	        context.start();
	        try {
	        	Thread.sleep(10000);
	        }catch (Exception e) {
				e.printStackTrace();
			}
	
	        // stop the CamelContext
	        context.stop();
        }
    }
}