package camelinaction;

public interface Status {
	void update(WagerResult res);
}

class WagerStatus implements Status {
	private String uuid;
	private boolean win = false;
	private boolean updated = false;
	
	public void update(WagerResult res) {
		if (!updated && uuid.equals(res.getUUID())) {
			if (res.getWin()) 
				win = true;
			updated = true;
		}
	}
	
	public void setUUID(String uuid) {
		this.uuid = uuid;
	}
	
	public String getUUID() {
		return uuid;
	}
	
	public boolean getWin() {
		return win;
	}
	
	public boolean getUpdated() {
		return updated;
	}
	
	public void setWin() {
		win = true;
	}
	
	public void setUpdated() {
		updated = true;
	}
}