import java.io.IOException;
import java.util.HashMap;
import java.util.Map;
import java.util.StringTokenizer;
import org.apache.hadoop.conf.Configuration;
import org.apache.hadoop.fs.Path;
import org.apache.hadoop.io.LongWritable;
import org.apache.hadoop.io.Text;
import org.apache.hadoop.mapreduce.Job;
import org.apache.hadoop.mapreduce.Mapper;
import org.apache.hadoop.mapreduce.Reducer;
import org.apache.hadoop.mapreduce.lib.input.FileInputFormat;
import org.apache.hadoop.mapreduce.lib.output.FileOutputFormat;

public class InvertedIndexBigrams {

	/*
	 * This is the Mapper class. It extends the Hadoop's Mapper class.
	 * This maps input key/value pairs to a set of intermediate(output) key/value pairs.
	 * Here our input key is a LongWritable and input value is a Text.
	 * And the output key is a Text and value is an Text.
	 */
	public static class InvertedIndexMapper extends Mapper<LongWritable, Text, Text, Text> {
		/*
		 * Hadoop supported data types. IntWritable -> Java's Integer, Text -> Java's String.
		 * Here, 'one' is the number of occurrences of the 'word' and is set to the value 1 during
		 * the Map process.
		 */
		private Text docID = new Text();
	    private Text word = new Text();
	    
	    public void map(LongWritable key, Text value, Context context) 
	    	throws IOException, InterruptedException {
	    	// Reading input one line at a time and tokenizing.
	    	String line = value.toString();
	    	StringTokenizer tokenizer = new StringTokenizer(line);
	    	docID.set(tokenizer.nextToken());
	    	String prev = null;
	    	
	    	// Iterating through all the words available in that line and forming the key/value pair.
	    	while (tokenizer.hasMoreTokens()) {
	    		String token = tokenizer.nextToken().toLowerCase();
	    		String[] subTokens = token.split("[^a-z]+");
	    		for (String subToken : subTokens) {
	    			if (subToken.trim().length() > 0) {
	    				String cur = subToken.trim();
	    				if (prev != null) {
	    					String bigram = (prev + " " + cur);
	    					word.set(bigram);
		    	    		context.write(word, docID);
	    				}
	    				prev = cur;
	    			}
	    		}
	    	}
	    }   
	}
	
	/*
	 * This is the Reducer class. It extends the Hadoop's Reducer class.
	 * This maps the intermediate key/value pairs we get from the Mapper to a set 
	 * of output key/value pairs, where the key is the word and the value is the word's count.
	 * * Here our input key is a Text and input value is an Text.
	 * And the output key is a Text and value is an Text.
	 */
	public static class InvertedIndexReducer extends Reducer<Text, Text, Text, Text> {
		/*
		 * Reduce method collects the output of the Mapper and adds the 1's to get the word's count.
		 */
		public void reduce(Text key, Iterable<Text> values, Context context) 
			throws IOException, InterruptedException {
			Map<String, Long> map = new HashMap<>();
			for (Text value : values) {
				String str = value.toString();
				map.put(str, map.getOrDefault(str, (long)0) + 1);
			}
			context.write(key, new Text(format(map)));
		}
		
		private String format(Map<String, Long> count) {
			String docs = "";
			int num = 0, size = count.size();
			for (String id : count.keySet()) {
				docs += id + ":" + count.get(id);
				if (++num < size) docs += "\t";
			}
			return docs;
		}
	}
	
	public static void main(String[] args) 
		throws IOException, ClassNotFoundException, InterruptedException {
		if (args.length != 2) {
			System.err.println("Usage: Word Count <input path> <output path>");
			System.exit(-1);
		}
		// Creating a Hadoop job and assigning a job name for identification.
		Configuration config = new Configuration();
		Job job = Job.getInstance(config, "Inverted Index Bigrams");
		job.setJarByClass(InvertedIndexBigrams.class);
		// The HDFS input and output directories to be fetched from the Dataproc job submission console.
		FileInputFormat.addInputPath(job, new Path(args[0]));
	    FileOutputFormat.setOutputPath(job, new Path(args[1]));
	    // Providing the mapper and reducer class names.
	    job.setMapperClass(InvertedIndexMapper.class);
	    job.setReducerClass(InvertedIndexReducer.class);
	    // Setting the job object with the data types of output key(Text) and value(IntWritable).
	    job.setOutputKeyClass(Text.class);
	    job.setOutputValueClass(Text.class);
	    System.exit(job.waitForCompletion(true) ? 0 : 1);
	}

}