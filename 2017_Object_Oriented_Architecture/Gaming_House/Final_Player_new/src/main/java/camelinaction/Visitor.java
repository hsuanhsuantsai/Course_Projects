package camelinaction;

public interface Visitor {
	public void visit(WagerOdds wo);
	public double getWinnings();
	public void resetWinnings();
}

class WinningsCounter implements Visitor {
	private double winnings = 0;
	private OddConverter oc;
	
	public WinningsCounter(OddConverter oc) {
		this.oc = oc;
	}
	
	public void visit(WagerOdds wo) {
		if (!wo.getCounted() && wo.getUpdated() && wo.getWin()) {
			if (wo.isParlay() && wo.isReady()) {
				if (winnings == 0.0) 
					winnings = wo.getStake();
				winnings *= oc.convert("EUR", wo.getOdds());
			}
			else 
				winnings += wo.getStake()*oc.convert("EUR", wo.getOdds());
		}
		if (wo.getUpdated() && (!wo.isParlay() || wo.isReady())) 
				wo.setCounted();
	}
	
	public double getWinnings() {
		return winnings;
	}
	
	public void resetWinnings() {
		winnings = 0.0;
	}
}