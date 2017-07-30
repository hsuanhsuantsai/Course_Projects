package camelinaction;

public abstract class OddConverter {
	abstract double convert(String type, double odds);
}

//convert odds from Eur to other representation
class EurOddConverter extends OddConverter {
	@Override
	double convert(String type, double odds) {
		//convert to UK
		int tmp = (int)(odds*100 - 100);
		double uk = tmp/100.0;
		if (type.equals("UK"))
			return uk;
		else if (type.equals("US")) {
			if (uk > 1)
				return 100*uk;
			else if (uk < 1)
				return -100/uk;
		}
		return odds;
	}
}

//convert odds from US to other representation
class USOddConverter extends OddConverter{
	@Override
	double convert(String type, double odds) {
		double ret = 0.0;
		//convert to UK
		if (odds > 0)
			ret = odds/100;
		else if (odds < 0)
			ret = 100/(-odds);
		
		if (type.equals("EUR"))
			return ret+1;
		else if (type.equals("UK")) 
			return ret;
		
		return odds;
	}
}

//convert odds from UK to other representation
class UKOddConverter extends OddConverter {
	@Override
	double convert(String type, double odds) {
		if (type.equals("EUR")) 
			return odds + 1;
		else if (type.equals("US")) {
			if (odds > 1)
				return 100*odds;
			else if (odds < 1)
				return -100/odds;
		}
		return odds;
	}
}