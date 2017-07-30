package camelinaction;

import java.io.BufferedReader;
import java.io.BufferedWriter;
import java.io.FileWriter;
import java.io.InputStreamReader;
import java.util.ArrayList;
import java.util.HashMap;
import java.util.Map;

import org.apache.activemq.ActiveMQConnectionFactory;
import org.apache.camel.CamelContext;
import org.apache.camel.Exchange;
import org.apache.camel.Processor;
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

public class Player {

	private static String player_name;
	private static Map<String, Wager> my_wagers = new HashMap<>();
	private static String[] data = null;
	private static ArrayList<String> wager_table = new ArrayList<>();
	private static Map<String, Wager> wager_map = new HashMap<>();
	private static BufferedReader my_buffer = null;
	private static OddConverter my_converter = new EurOddConverter();
	private static String odd_type = "EUR";
	private static Visitor winningsCounter;
	private static Map<String, WagerResult> result_table = new HashMap<>();
	private static int errors = 0;
	
	//content based router pattern
	public static void sendRecord() {
		try {
			// create CamelContext
	        CamelContext context = new DefaultCamelContext();

	        // connect to ActiveMQ JMS broker listening on localhost on port 61616
	        ConnectionFactory connectionFactory = 
	        	new ActiveMQConnectionFactory("tcp://localhost:61616");
	        context.addComponent("jms", JmsComponent.jmsComponentAutoAcknowledge(connectionFactory));
	        
	        // add our route to the CamelContext
	        context.addRoutes(new RouteBuilder() {
	            public void configure() {
	            	from("file:data/record")
	            	.choice()
	            		.when(body().regex(".*Total Points.*")).to("jms:Total_Points")
	            		.when(body().regex(".*Point Spread.*")).to("jms:Point_Spread")
	            		.when(body().regex(".*Single.*")).to("jms:Single");
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
	
	public static void bet_single(String uuid, int stake) {
		Wager wager = wager_map.get(uuid);
		if (wager == null) {
			System.out.println("Failure!");
			return;
		}
		wager.setStake(stake);
		WagerStatus ws = ((SingleWager)wager).getWS();
		if (ws == null)
			System.out.println("WS NULL");
		String title = wager.getGame();
		WagerResult wr = result_table.get(title);
		if (wr == null)
			System.out.println("WR NULL");
		wr.attach(ws);
		my_wagers.put(uuid, wager);
		try (
				BufferedWriter my_writer = new BufferedWriter(new FileWriter("data/record/" + uuid, true));
			){
				my_writer.write(uuid + "," + player_name + "," + wager.get_type() + "," + stake);
		}catch (Exception e) {
            e.printStackTrace();
        }
		sendRecord();
		System.out.println("Success!");
	}
	
	public static void bet_parlay(String name) {
		Wager my_parlay = new Parlay(name);
		boolean flag = true;
		
		try {
			my_buffer = new BufferedReader(new InputStreamReader(System.in));
			System.out.println("Enter how many stakes you want to spend");
			String tmp = my_buffer.readLine();
			int stake = Integer.parseInt(tmp);
			
			while(flag) {
				System.out.println("Enter the UUID or -1 to finish.");
				String id = my_buffer.readLine();
				if (id.equals("-1")){
					flag = false;
					continue;
				}
				
				Wager w = wager_map.get(id);
				if (w != null) {
					w.setStake(stake);
					WagerStatus ws = ((SingleWager)w).getWS();
					if (ws == null)
						System.out.println("WS NULL");
					String title = w.getGame();
					WagerResult wr = result_table.get(title);
					if (wr == null)
						System.out.println("WR NULL");
					wr.attach(ws);
					try (
							BufferedWriter my_writer = new BufferedWriter(new FileWriter("data/record/" + id, true));
						){
							my_writer.write(id + "," + player_name + "," + w.get_type() + "," + stake);
					}catch (Exception e) {
			            e.printStackTrace();
			        }
					my_parlay.add_wager(w);
				}
				else
					System.out.println("Please enter a valid integer!");
			}
		}catch (Exception e) {
				e.printStackTrace();
		}

		my_wagers.put(name, my_parlay);
		sendRecord();
		System.out.println("Success!");
	}
	
	public static void make_wager_table(String[] wager_list) {
		for (int i=0; i<wager_list.length; i++) {
			String[] parse = wager_list[i].split(",");
			double odds = Double.parseDouble(parse[4]);	//EUR-based
			OddConverter tmp = new EurOddConverter();
			double convertedOdds = tmp.convert(odd_type, odds);
			wager_map.put(parse[0], new SingleWager(parse[0], parse[1], parse[2], parse[3], convertedOdds));
			WagerResult	wr = result_table.get(parse[1]);
			if (wr == null) {
				result_table.put(parse[1], new WagerResult());
				if (result_table.get(parse[1]) == null)
					System.out.println("HERE NULL");
			}
			wager_table.add("UUID: " + parse[0] + "| Title: " + parse[1] + "| Type: " + parse[2] + 
							"| Description: " + parse[3] + "| Odds: " + convertedOdds);
		}
	}
	
	public static void show_table() {
		for (int i=0; i<wager_table.size(); i++)
			System.out.println(wager_table.get(i));
	}
	
	public static void query_all() {
		if (my_wagers.size() == 0)
			System.out.println("You have no wagers now.");
		for (Map.Entry<String, Wager> entry: my_wagers.entrySet())
			entry.getValue().print_detail();
	}
	
	public static boolean choose_converter() {
		
		if (odd_type.equals("EUR")) 
			my_converter = new EurOddConverter();
		else if (odd_type.equals("US")) 
			my_converter = new USOddConverter();
		else if (odd_type.equals("UK")) 
			my_converter = new UKOddConverter();
		else
			return false;
		
		return true;
	}
	
	public static void payoff() {
		if (winningsCounter == null)
			winningsCounter = new WinningsCounter(my_converter);
		for (Map.Entry<String, Wager> entry: my_wagers.entrySet()) 
			entry.getValue().getVisitable().accept(winningsCounter);
		
		System.out.println("Your payoff is: " + winningsCounter.getWinnings());
	}
	
	public static void request_reply() {
		try {
            // Create a ConnectionFactory
            ActiveMQConnectionFactory connectionFactory = new ActiveMQConnectionFactory("tcp://localhost:61616");

            // Create a Connection
            Connection connection = connectionFactory.createConnection();
            connection.start();

            // Create a Session
            Session session = connection.createSession(false, Session.AUTO_ACKNOWLEDGE);

            // Create the destination (Topic or Queue)
            Destination destination = session.createQueue("Requesters");

            // Create a MessageProducer from the Session to the Topic or Queue
            MessageProducer producer = session.createProducer(destination);
            producer.setDeliveryMode(DeliveryMode.NON_PERSISTENT);

            // Create a messages
            String text = player_name;
            
            //Dead letter pattern
            if (text.equals("") || text == null) {
            	Destination dest = session.createQueue("Dead Letter");
            	MessageProducer producer2 = session.createProducer(dest);
                producer2.setDeliveryMode(DeliveryMode.NON_PERSISTENT);
            	TextMessage message = session.createTextMessage(text);
            	producer2.send(message);
            	System.err.println("Invalid Player's Name!");
            	System.exit(-1);
            }
            
            TextMessage message = session.createTextMessage(text);

            // Tell the producer to send the message
            producer.send(message);

            // Create the destination (Topic or Queue)
            Destination destination_2 = session.createQueue(player_name);
            
            // Create a MessageConsumer from the Session to the Topic or Queue
            MessageConsumer consumer = session.createConsumer(destination_2);

            // Wait for a message until time out
            Message message2 = consumer.receive(1000);

            if (message2 instanceof TextMessage) {
                TextMessage textMessage = (TextMessage) message2;
                String content = textMessage.getText();
                
                boolean invalid = false;
                //Invalid message channel
                //no , in message or incorrect content composition
                if (content.indexOf(",") == -1) {
                	Destination dest = session.createQueue("Invalid message");
                	MessageProducer producer2 = session.createProducer(dest);
                    producer2.setDeliveryMode(DeliveryMode.NON_PERSISTENT);
                	TextMessage msg = session.createTextMessage(content);
                	producer2.send(msg);
                	data = null;
                	errors++;
                }
                else {
                	data = content.split("\n");
                	for (int i=0; i<data.length; i++) {
                		String [] tmp = data[i].split(",");
                		if (tmp.length != 5) {
                			invalid = true;
                			break;
                		}
                	}
                	
                	if (invalid) {
                		Destination dest = session.createQueue("Invalid message");
                    	MessageProducer producer2 = session.createProducer(dest);
                        producer2.setDeliveryMode(DeliveryMode.NON_PERSISTENT);
                    	TextMessage msg = session.createTextMessage(content);
                    	producer2.send(msg);
                		data = null;
                		errors++;
                	}
                }
            } else {
                System.out.println("Received: " + message2);
                data = null;
            }

            // Clean up
            consumer.close();
            session.close();
            connection.close();
            
            Thread.sleep(5000);
        }
        catch (Exception e) {
            e.printStackTrace();
        }
	}
	
	public static void subscribe() {
		try {
			// create CamelContext
	        CamelContext context = new DefaultCamelContext();

	        // connect to ActiveMQ JMS broker listening on localhost on port 61616
	        ConnectionFactory connectionFactory = new ActiveMQConnectionFactory("tcp://localhost:61616");
	        context.addComponent("jms", JmsComponent.jmsComponentAutoAcknowledge(connectionFactory));
	        
	        // add our route to the CamelContext
		    context.addRoutes(new RouteBuilder() {
		    	public void configure() {
		    		from("jms:topic:Results_Topic")
		            .process(new Processor(){
		            	public void process(Exchange exchange) throws Exception {
		            		String[] res = exchange.getIn().getBody(String.class).split(",");
		            		WagerResult wr = result_table.get(res[1]);
			                wr.setUUID(res[0]);
			                res[2] = res[2].replaceAll("\n", "");
			                wr.setWin(res[2].equals("win"));
			                wr.update();
		            	}
		            }).to("jms:BACKUP");
		        }
		    });

	        // start the route and let it do its work
	        context.start();
	        Thread.sleep(15000);

	        // stop the CamelContext
	        context.stop();
		}catch (Exception e) {
            e.printStackTrace();
        }
	}
	
	public static void main(String[] args) {
		
		try {
			System.out.println("Welcome to our online NBA gaming house!!");
			my_buffer = new BufferedReader(new InputStreamReader(System.in));
			String input = null;
			System.out.println("What's your name?");
			player_name = my_buffer.readLine();
			System.out.println("Please choose odds representation(EUR/US/UK)");
			input = my_buffer.readLine();
			odd_type = input.toUpperCase();
			while(!choose_converter()) {
				System.out.println("Invalid input! \nPlease enter EUR/US/UK");
				input = my_buffer.readLine();
				odd_type = input.toUpperCase();
			}
			
			while (true) {
				System.out.println("Please take an action");
				System.out.println("show/single wager/parlay/query/results/payoff/quit");
				input = my_buffer.readLine();
				if (input.equalsIgnoreCase("show")) {
					request_reply();
					while(data == null) {
						if (errors == 3) {
							System.err.println("Something went wrong, try again later!");
							System.exit(-1);
						}
						request_reply();
					}
					wager_table.removeAll(wager_table);
					make_wager_table(data);
					show_table();
				}
				else if (input.equalsIgnoreCase("single wager")) {
					System.out.println("Enter the UUID.");
					input = my_buffer.readLine();
					System.out.println("Enter how many stakes you want to spend");
					String tmp = my_buffer.readLine();
					int stake;
					while(true) {
						try {
							stake = Integer.parseInt(tmp);
							break;
						}catch (Exception e) {
							System.out.println("Please enter a valid integer!");
							tmp = my_buffer.readLine();
						}
					}
					
					bet_single(input, stake);
				}
				else if (input.equalsIgnoreCase("parlay")) {
					System.out.println("Enter your parlay name");
					input = my_buffer.readLine();
					bet_parlay(input);
				}
				else if (input.equalsIgnoreCase("query")) {
					System.out.println("Enter all/uuid/parlay name");
					input = my_buffer.readLine();
					if (input.equalsIgnoreCase("all"))
						query_all(); 
					else {
						Wager wager = my_wagers.get(input);
						if (wager == null) {
							System.out.println("Wager not found, try another uuid again.");
							continue;
						}
						wager.print_detail();
						if (!wager.isParlay()) {
							System.out.println("Show in different odds representation?(y/n)");
							input = my_buffer.readLine();
							if (input.equalsIgnoreCase("y")) {
								System.out.println("Please enter a type(EUR/US/UK)");
								input = my_buffer.readLine();
								SingleWager sw = (SingleWager)wager;
								System.out.println(my_converter.convert(input, sw.get_odds()));
							}
						}
					}
				}
				else if (input.equalsIgnoreCase("results"))
					subscribe();
				else if (input.equalsIgnoreCase("payoff")) {
					payoff();
					System.out.println("Take your payoff away?(y/n)");
					input = my_buffer.readLine();
					if (input.equalsIgnoreCase("y")) {
						System.out.println("Here you go~");
						winningsCounter.resetWinnings();
					}
				}
				else if (input.equalsIgnoreCase("quit")) {
					System.out.println("See you next time!");
					System.exit(0);
				}
			}
		}catch (Exception e) {
			e.printStackTrace();
		}
	}

}