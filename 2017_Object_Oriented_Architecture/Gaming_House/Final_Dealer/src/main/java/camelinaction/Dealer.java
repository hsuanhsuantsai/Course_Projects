package camelinaction;

import java.io.BufferedReader;
import java.io.InputStreamReader;
import java.util.ArrayList;
import java.util.UUID;

import org.apache.activemq.ActiveMQConnectionFactory;
import org.apache.camel.CamelContext;
import org.apache.camel.builder.RouteBuilder;
import org.apache.camel.component.jms.JmsComponent;
import org.apache.camel.impl.DefaultCamelContext;

import javax.jms.Connection;
import javax.jms.ConnectionFactory;
import javax.jms.DeliveryMode;
import javax.jms.Destination;
import javax.jms.Message;
import javax.jms.MessageConsumer;
import javax.jms.MessageProducer;
import javax.jms.Session;
import javax.jms.TextMessage;

public class Dealer {

	private static ArrayList<String> wager_table = new ArrayList<>();
	
	private static class RequestReplyThread implements Runnable {
		private String requester = null;
		
		public void run() {
			try {
	            // Create a ConnectionFactory
	            ActiveMQConnectionFactory connectionFactory = new ActiveMQConnectionFactory("tcp://localhost:61616");

	            // Create a Connection
	            Connection connection = connectionFactory.createConnection();
	            connection.start();

//	            connection.setExceptionListener(this);

	            // Create a Session
	            Session session = connection.createSession(false, Session.AUTO_ACKNOWLEDGE);

	            // Create the destination (Topic or Queue)
	            Destination destination = session.createQueue("Requesters");
	            
	            // Create a MessageConsumer from the Session to the Topic or Queue
	            MessageConsumer consumer = session.createConsumer(destination);

	            while(true) {
		            // Wait for a message until time out
		            Message message = consumer.receive(5000);
	
		            if (message instanceof TextMessage) {
		                TextMessage textMessage = (TextMessage) message;
		                String text = textMessage.getText();
		                System.out.println("Received: " + text);
		                requester = text;
		                Destination destination_2 = session.createQueue(requester);
		                
		                // Create a MessageProducer from the Session to the Topic or Queue
		                MessageProducer producer = session.createProducer(destination_2);
		                producer.setDeliveryMode(DeliveryMode.NON_PERSISTENT);
		                
		                // Create a message
		                String msg = "";
		                for (int i=0; i<wager_table.size(); i++)
		                	msg += wager_table.get(i) + "\n";
		                TextMessage message2 = session.createTextMessage(msg);
		                
		                producer.send(message2);
		            } else {
		                System.out.println("Received: " + message);
		            }
		            Thread.sleep(1000);
	            }
//	            consumer.close();
//	            session.close();
//	            connection.close();
	        } catch (Exception e) {
	            e.printStackTrace();
	        }
		}
	}
	
	private static class PublisherThread implements Runnable {
		public void run() {
			try {
				// create CamelContext
		        CamelContext context = new DefaultCamelContext();

		        // connect to ActiveMQ JMS broker listening on localhost on port 61616
		        ConnectionFactory connectionFactory = new ActiveMQConnectionFactory("tcp://localhost:61616");
		        context.addComponent("jms", JmsComponent.jmsComponentAutoAcknowledge(connectionFactory));
		        
		        // add our route to the CamelContext
		        context.addRoutes(new RouteBuilder() {
		            public void configure() {
		            	from("file:data/results?noop=true").to("jms:Results");
		            }
		        });

		      
			    // start the route and let it do its work
			    context.start();
			    Thread.sleep(10000);
	
			    // stop the CamelContext
			    context.stop();
			}catch (Exception e) {
				e.printStackTrace();
			}
		}
	}

	//Debug purpose
	private static void wager_print() {
		for (int i=0; i<wager_table.size(); i++)
        	System.out.println(wager_table.get(i));
	}
	
	public static void main(String[] args) {
		//default input here
		wager_table.add("12345,Cavaliers vs Warriors G2,Total Points,greater than 189,1.80");
		wager_table.add("12346,Cavaliers vs Warriors G2,Total Points,greater than 180,1.20");
		wager_table.add("12347,Warriors vs Cavaliers G3,Point Spread,within 5,1.30");
		wager_table.add("22345,Cavaliers vs Warriors G4,Single,Warriors win,1.30");
		wager_table.add("22346,Cavaliers vs Warriors G4,Single,Cavaliers win,2.30");
		wager_table.add("22347,Cavaliers vs Warriors G4,Total Points,greater than 200,1.10");
		wager_table.add("22348,NBA Final Champion,Single,Cavaliers,5.00");
		
		//include this line for invalid message
//		wager_table.add("2234");
		
		new Thread(new RequestReplyThread()).start();
		
		try (
				BufferedReader my_buffer = new BufferedReader(new InputStreamReader(System.in));
			){
				while(true) {
					System.out.println("Take an action: Add/Publish");
					String input = my_buffer.readLine();
					
					if (input.equalsIgnoreCase("Add")) {
						while(true) {
							System.out.println("new_wager/quit");
							String new_wager = my_buffer.readLine();
						
							if (new_wager.equalsIgnoreCase("quit"))
								break;
							wager_table.add(UUID.randomUUID().toString() + "," + new_wager);
							wager_print();
						}
					}
					else if (input.equalsIgnoreCase("Publish"))
						new Thread(new PublisherThread()).start();
				}
			}catch (Exception e) {
				e.printStackTrace();
			}

	}

}