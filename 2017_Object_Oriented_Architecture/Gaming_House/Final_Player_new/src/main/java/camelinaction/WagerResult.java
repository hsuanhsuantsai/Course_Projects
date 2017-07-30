package camelinaction;

import java.util.ArrayList;

public class WagerResult {
	protected String uuid;
	protected boolean win;
	private ArrayList<WagerStatus> wager_status = new ArrayList<>();
	
	public void attach(WagerStatus ws) {
		wager_status.add(ws);
	}
	
	public void detach(WagerStatus ws) {
		wager_status.remove(ws);
	}
	
	public void update() {
		for (WagerStatus i:wager_status)
			i.update(this);
	}
	
	public String getUUID() {
		return uuid;
	}
	
	public void setUUID(String uuid) {
		this.uuid = uuid;
	}
	
	public void setWin(boolean win) {
		this.win = win;
	}
	
	public boolean getWin() {
		return win;
	}
}