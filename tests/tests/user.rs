extern crate postmill;

mod common;

use postmill::Client;

#[test]
fn login() {
    let mut client = Client::new(&*common::active_url().unwrap()).unwrap();
    client.login("rust_postmill_test", "rust_postmill_test").unwrap();
}

#[test]
#[should_panic]
fn login_non_existing_user() {
    let mut client = Client::new(&*common::active_url().unwrap()).unwrap();
    client.login("non_existing_user", "non_existing_password").unwrap();
}

#[test]
fn comment_post() {
    let mut client = Client::new(&*common::active_url().unwrap()).unwrap();
    client.login("rust_postmill_test", "rust_postmill_test").unwrap();
    client.comment_post(1, "Test comment").unwrap();
}

#[test]
fn submit_post() {
    let mut client = Client::new(&*common::active_url().unwrap()).unwrap();
    client.login("rust_postmill_test", "rust_postmill_test").unwrap();
    client.submit_post("TestGround", "https://git.sr.ht/~foss/postmill", "Test submission title", "Test submission body").unwrap();
}

#[test]
fn submit_and_edit_post() {
    let mut client = Client::new(&*common::active_url().unwrap()).unwrap();
    client.login("rust_postmill_test", "rust_postmill_test").unwrap();
    let post_id = client.submit_post("TestGround", "https://git.sr.ht/~foss/postmill", "Test submission title", "Test submission body").unwrap();
    client.edit_post(post_id, Some("https://git.sr.ht/~foss/postmill/log"), Some("Test submission title - edited"), Some("Test submission body - edited")).unwrap();
}

#[test]
fn reply_comment() {
    let mut client = Client::new(&*common::active_url().unwrap()).unwrap();
    client.login("rust_postmill_test", "rust_postmill_test").unwrap();
    client.reply_comment(1, 1, "Test reply comment").unwrap();
}
