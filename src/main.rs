use std::error::Error;
use postmill::Client;

fn main() -> Result<(), Box<Error>> {
let mut client = Client::new("http://192.168.33.10")?;

// Login
client.login("araiy", "q1w2e3r4")?;

// Submit a new post
client.submit_post("TestGround", "https://git.sr.ht/~foss/postmill", "Test submission title", "Test submission body")?;
Ok(())
}
