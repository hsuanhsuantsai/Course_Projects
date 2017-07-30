package camelinaction;

import java.util.ArrayList;

public interface Visitable {
	public void accept(Visitor visitor);
	
	public boolean getWin();
	public void setWin();
	public boolean getUpdated();
	public void setUpdated();
	public void setParlay();
	public void setReady();
}

class WagerOdds implements Visitable {
	private double odds = 1;
	private boolean counted = false;
	private WagerStatus ws = new WagerStatus();
	private int stake = 0;
	private boolean parlay = false;
	private boolean ready = false;			//make sure results of all wagers in parlay are published
	
	public void accept(Visitor visitor) {
		visitor.visit(this);
	}
	
	public boolean getWin() {
		return ws.getWin();
	}
	
	public void setWin() {
		ws.setWin();
	}
	
	public boolean getUpdated() {
		return ws.getUpdated();
	}
	
	public void setUpdated() {
		ws.setUpdated();
	}
	
	public void setOdds(double odds) {
		this.odds = odds;
	}
	
	public double getOdds() {
		return odds;
	}
	
	public String getUUID() {
		return ws.getUUID();
	}
	
	public void setUUID(String uuid) {
		ws.setUUID(uuid);
	}
	
	public boolean getCounted() {
		return counted;
	}
	
	public void setCounted() {
		counted = true;
	}
	
	public int getStake() {
		return stake;
	}
	
	public void setStake(int stake) {
		this.stake = stake;
	}
	
	public WagerStatus getWS() {
		return ws;
	}
	
	public void setParlay() {
		parlay = true;
	}
	
	public boolean isParlay() {
		return parlay;
	}
	
	public boolean isReady() {
		return parlay && ready;
	}
	
	public void setReady() {
		ready = true;
	}
}

class ParlayOdds implements Visitable {
	private ArrayList<Visitable> my_visitables = new ArrayList<>();
	private ArrayList<Wager> my_wagers;
	
	public ParlayOdds(ArrayList<Wager> my_wagers) {
		this.my_wagers = my_wagers;
	}
	
	public void accept(Visitor visitor) {
		make_visitable();
		if (checkAllUpdated())
			setAllReady();
		//all wagers win then we can count
		if (getWin()) {
			for (int i=0; i<my_visitables.size(); i++)
				my_visitables.get(i).accept(visitor);
		}
	}
	
	public void make_visitable() {
		for (int i=0; i<my_wagers.size(); i++) {
			my_wagers.get(i).getVisitable().setParlay();
			my_visitables.add(my_wagers.get(i).getVisitable());
		}
	}
	
	public boolean getWin()  {
		boolean win = true;
		for (int i=0; i<my_visitables.size(); i++)
			win = win && my_visitables.get(i).getWin();
		return win;
	}
	
	public boolean checkAllUpdated() {
		boolean flag = true;
		
		for (int i=0; i<my_visitables.size(); i++)
			flag |= my_visitables.get(i).getUpdated();
		
		return flag;
	}
	
	public void setAllReady() {
		for (int i=0; i<my_visitables.size(); i++)
			my_visitables.get(i).setReady();
	}
	
	//not in use
	public void setWin() {
		
	}
	
	//not in use
	public boolean getUpdated() {
		return false;
	}
	
	//not in use
	public void setUpdated() {
		
	}
	
	//not in use
	public void setParlay() {
		
	}
	
	//not in use
	public void setReady() {
		
	}
}