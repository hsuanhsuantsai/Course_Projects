package camelinaction;

import java.util.ArrayList;

public abstract class Wager {
	protected String game;	//game name; what to bet on

	public Wager(String game) {
		this.game = game;
	}
	
	abstract public String getGame();
	abstract public void add_wager(Wager w);
	abstract public void print_detail();
	abstract public boolean isParlay();
	abstract public Visitable getVisitable();
	abstract public void setStake(int stake);
	abstract public String get_type();
}

class SingleWager extends Wager {
	private String type;			//point spread, total points etc.
	private String description;		//winning condition
	private WagerOdds wo = new WagerOdds();
	
	public SingleWager(String uuid, String game, String type, String description, double odds) {
		super(game);
		this.type = type;
		this.description = description;
		wo.setUUID(uuid);
		wo.setOdds(odds);
		
	}
	
	public String getGame() {
		return game;
	}
	
	//not in use
	public void add_wager(Wager w) {
		return;
	}
	
	public void print_detail() {
		System.out.println("Title: " + game + "| Type: " + type + "| Description: " + description + "| Odds: " + wo.getOdds() + "| Stake: " + wo.getStake());
	}
	
	public boolean isParlay() {
		return false;
	}
	
	public Visitable getVisitable() {
		return wo;
	}
	
	public void setStake(int stake) {
		wo.setStake(stake);
	}
	
	public String get_uuid() {
		return wo.getUUID();
	}
	
	public String get_type() {
		return type;
	}
	
	public String get_description() {
		return description;
	}
	
	public double get_odds() {
		return wo.getOdds();
	}
	
	//debug purpose
	public WagerStatus getWS() {
		return wo.getWS();
	}
	
}

class Parlay extends Wager {
	private ArrayList<Wager> my_wagers = new ArrayList<>();
	private ParlayOdds po = new ParlayOdds(my_wagers);
	
	public Parlay(String game) {
		super(game);
	}
	
	public ArrayList<Wager> get_wagers() {
		return my_wagers;
	}
	
	public String getGame() {
		return game;
	}
	
	public void add_wager(Wager w) {
		my_wagers.add(w);
	}
	
	public void print_detail() {
		System.out.println("\nParlay: " + game);
		for (int i=0; i<my_wagers.size(); i++)
			my_wagers.get(i).print_detail();
		System.out.println("End of Parlay\n");
	}
	
	public boolean isParlay() {
		return true;
	}
	
	public Visitable getVisitable() {
		return po;
	}
	
	//not in use
	public void setStake(int stake){
		return;
	}
	
	//not in use
	public String get_type() {
		return "Parlay";
	}
}