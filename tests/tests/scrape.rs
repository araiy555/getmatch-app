extern crate postmill;

mod common;

use postmill::Client;
use chrono::Duration;
use chrono::prelude::*;

#[test]
fn forums() {
    let mut raddle = Client::new(&*common::active_url().unwrap()).unwrap();
    assert_eq!(raddle.forums().unwrap().contains(&"TestGround".to_string()), true);
}

#[test]
fn submission_urls() {
    let mut raddle = Client::new(&*common::active_url().unwrap()).unwrap();
    assert!(raddle.submission_urls_from_page("new").unwrap().len() > 0);
}

#[test]
fn submissions_from_page() {
    let mut raddle = Client::new(&*common::active_url().unwrap()).unwrap();
    assert!(raddle.submissions_from_page("new").unwrap().len() > 0);
}

#[test]
fn submissions_from_page_until_all_new() {
    let mut raddle = Client::new(&*common::active_url().unwrap()).unwrap();
    let time_2days_before = (Utc::now() - Duration::days(2)).with_timezone(&FixedOffset::east(0));
    raddle.submissions_from_page_until("all/new", time_2days_before).unwrap();
}

#[test]
fn submissions_from_page_until_featured_new() {
    let mut raddle = Client::new(&*common::active_url().unwrap()).unwrap();
    let time_2days_before = (Utc::now() - Duration::days(2)).with_timezone(&FixedOffset::east(0));
    raddle.submissions_from_page_until("featured/new", time_2days_before).unwrap();
}

#[test]
fn submissions_from_page_until_forum() {
    let mut raddle = Client::new(&*common::active_url().unwrap()).unwrap();
    let time_2days_before = (Utc::now() - Duration::days(2)).with_timezone(&FixedOffset::east(0));
    raddle.submissions_from_page_until("f/TestGround", time_2days_before).unwrap();
}

#[test]
fn comments_until() {
    let mut raddle = Client::new(&*common::active_url().unwrap()).unwrap();
    let time_2days_before = (Utc::now() - Duration::days(2)).with_timezone(&FixedOffset::east(0));
    raddle.comments_until(time_2days_before).unwrap();
}
