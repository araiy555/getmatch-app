#![crate_name = "postmill"]

//! A HTTP API to interface with Postmill sites.
//!
//! This should make writing bots a lot easier.
//!
//! # Examplesmod
//!
//! ```rust
//! # use std::error::Error;
//! use postmill::Client;
//!
//! # fn main() -> Result<(), Box<Error>> {
//! let mut client = Client::new("https://raddle.me")?;
//!
//! // Login
//! client.login("rust_postmill_test", "rust_postmill_test")?;
//!
//! // Submit a new post
//! client.submit_post("TestGround", "https://git.sr.ht/~foss/postmill", "Test submission title", "Test submission body")?;
//! # Ok(())
//! # }
//! ```
//!
//! ```rust
//! # use std::error::Error;
 use postmill::Client;
//!
//! # fn main() -> Result<(), Box<Error>> {
//! let mut client = Client::new("https://raddle.me")?;
//!
//! // Print all the submission titles of a page
//! for submission in client.submissions_from_page("new")? {
//!     println!("Title: {}", submission.title);
//! }
//! # Ok(())
//! # }
//! ```

use std::error::Error;
use url::Url;
use select::document::Document;
use select::predicate::{And, Attr, Class, Name, Not, Predicate};
use cookie::{Cookie, CookieJar};
use reqwest::header::{HeaderMap, HeaderValue};
use chrono::prelude::*;

#[derive(Debug)]
pub struct SubmissionInfo {
    /// The url of the article/site that the submission is pointing to.
    pub url: Url,
    /// The full title of the submission.
    pub title: String,
    /// The username of the author of the submission.
    pub author: String,
    /// The date the submission is posted.
    pub date: DateTime<FixedOffset>,
    /// The forum in which the submission is posted.
    pub forum: String,
    /// The submission id for the forum.
    pub id: u64,
}

#[derive(Debug)]
pub struct CommentInfo {
    /// The HTML body of the comment after it's parsed by markdown.
    pub body: String,
    /// The username of the author of the comment.
    pub author: String,
    /// The name of the forum the comment is posted in.
    pub forum: String,
    /// The date the comment is posted.
    pub date: DateTime<FixedOffset>,
    /// The comment id.
    pub id: u64,
    /// The id of the submission the comment is placed in.
    pub submission_id: u64,
}

pub struct Client {
    root_url: Url,
    http_client: reqwest::Client,
    csrf_token: Option<String>,
    session_cookies: CookieJar
}

impl Client {
    /// Constructs a new `Client`.
    /// This client contains the web session.
    pub fn new(root_url: &str) -> Result<Client, Box<Error>> {
        let client = reqwest::Client::builder()
            .default_headers(Client::default_headers())
            .redirect(reqwest::RedirectPolicy::none())
            .build()?;

        Ok(Client {
            root_url: Url::parse(root_url)?,
            http_client: client,
            csrf_token: None,
            session_cookies: CookieJar::new()
        })
    }

    /// Get the names of all the forums.
    pub fn forums(&mut self) -> Result<Vec<String>, Box<Error>> {
        let resp = self.do_get_request("forums/by_category")?;

        let document = Document::from_read(resp)?;

        Ok(document.find(Class("forum-group-list-item")).map(|item| item.text().trim().to_string()).collect())
    }

    /// Get a list of all the urls of submissions on a page.
    pub fn submissions_from_page(&mut self, url: &str) -> Result<Vec<SubmissionInfo>, Box<Error>> {
        let resp = self.do_get_request(url)?;

        let document = Document::from_read(resp)?;

        Ok(document.find(Class("submission-row")).map(|item| {
            let form_element = item.find(Name("form")).next().unwrap();
            let id = form_element.attr("action").unwrap().trim_start_matches("/sv/");

            let inner = item.find(Class("submission-inner")).next().unwrap();
            let url_element = item.find(Class("submission-link")).next().unwrap();
            let mut url = url_element.attr("href").unwrap().to_string();
            if url.starts_with("/") {
                url = format!("{}{}", self.root_url.clone(), url);
            }
            let title = url_element.text();

            let date_element = inner.find(Name("time")).next().unwrap();
            let date = date_element.attr("datetime").unwrap();
            let forum = inner.find(Attr("class", "submission-forum")).next().unwrap().text();
            let author = inner.find(Class("submission-info").descendant(Name("a"))).next()
                .expect("Could not find class='submission-info' with descendant 'a' in html source").text();

            SubmissionInfo {
                title: title,
                url: Url::parse(&*url).unwrap(),
                date: DateTime::parse_from_rfc3339(date).unwrap(),
                forum: forum,
                author: author,
                id: id.parse().unwrap()
            }
        }).collect())
    }

    /// Get a list of all the urls of submissions on a page from now until a certain date.
    pub fn submissions_from_page_until(&mut self, url: &str, date: DateTime<FixedOffset>) -> Result<Vec<SubmissionInfo>, Box<Error>> {
        let mut resp = self.do_get_request(url)?;

        let mut submissions = Vec::new();

        // Go to each page until we find the current date
        loop {
            let document = Document::from_read(resp)?;

            let mut date_encountered = false;
            submissions.extend(document.find(Class("submission-row")).map(|item| {
                let form_element = item.find(Name("form")).next()
                    .expect("Could not find element 'form' in html source");
                let id = form_element.attr("action")
                    .expect("Could not find attribute 'action' in html element 'form'")
                    .trim_start_matches("/sv/");

                let inner = item.find(Class("submission-inner")).next()
                    .expect("Could not find class 'submission-inner' in html source");
                let url_element = inner.find(Class("submission-link")).next()
                    .expect("Could not find class='submission-link' in html element 'submission-inner'");
                let mut url = url_element.attr("href")
                    .expect("Could not find attribute 'href' in html element 'submission-link'").to_string();
                if url.starts_with("/") {
                    url = format!("{}{}", self.root_url.clone(), url);
                }
                let title = url_element.text();

                let date_element = inner.find(Name("time")).next()
                    .expect("Could not find element 'time' in html source");
                let date = date_element.attr("datetime")
                    .expect("Could not find attribute 'datetime' in html element 'time'");
                let forum = match inner.find(Class("submission-forum")).next() {
                    Some(elem) => elem.text(),
                    None => "".to_string()
                };
                let author = inner.find(Class("submission-info").descendant(Name("a"))).next()
                    .expect("Could not find class='submission-info' with descendant 'a' in html source").text();

                SubmissionInfo {
                    title: title,
                    url: Url::parse(&*url).expect("Submission URL is not valid"),
                    date: DateTime::parse_from_rfc3339(date).expect("Submission date is not valid"),
                    forum: forum,
                    author: author,
                    id: id.parse().expect("Submission id is not valid")
                }
            })
            .filter(|sub| {
                // Remove everything that's before the date specified
                if sub.date <= date {
                    date_encountered = true;
                    return false;
                }

                return true;
            }));

            if date_encountered {
                return Ok(submissions);
            }

            // Find the url for the next page
            let next_element = match document.find(Class("pagination")).next() {
                Some(elem) => elem
                    .find(Class("next")).next()
                    .expect("Could not find class 'next' in html element 'pagination'")
                    .find(Name("a")).next()
                    .expect("Could not find name 'a' in html element 'next'"),
                // We are finished if there is no more pagination URL
                None => return Ok(submissions)
            };
            let next_url = next_element.attr("href")
                .expect("Could not find attribute 'href' in element 'a'")
                .to_string();
            let url = self.root_url.clone().join(&*next_url)?;
            resp = self.do_get_request_url(url)?;
        }
    }

    /// Get a list of all the urls of submissions on a page.
    pub fn submission_urls_from_page(&mut self, url: &str) -> Result<Vec<String>, Box<Error>> {
        let resp = self.do_get_request(url)?;

        let document = Document::from_read(resp)?;

        Ok(document.find(Class("submission-title")).map(|item| {
            let url_element = item.find(Name("a")).next().unwrap();
            url_element.attr("href").unwrap().to_string()
        }).collect())
    }

    /// Get a list of all the comments until a certain date.
    pub fn comments_until(&mut self, date: DateTime<FixedOffset>) -> Result<Vec<CommentInfo>, Box<Error>> {
        let mut resp = self.do_get_request("comments")?;

        let mut comments = Vec::new();

        // Go to each page until we find the current date
        loop {
            let document = Document::from_read(resp)?;

            let mut date_encountered = false;
            comments.extend(document.find(Class("comment")).map(|item| {
                let id = item.find(Name("form"))
                    .next()
                    .expect("Could not find element 'form' in html source")
                    .attr("action")
                    .expect("Could not find attribute 'action' in html element 'form'")
                    .trim_start_matches("/cv/");

                let submission_id = item.find(And(Name("a"), Not(Attr("Class", ()))))
                    .filter(|a| !a
                            .attr("href")
                            .expect("Could not find attribute 'href' in html element 'a'")
                            .starts_with("/user"))
                    .next()
                    .expect("Could not find element 'a' in html source")
                    .attr("href")
                    .expect("Could not find attribute 'href' in html element 'a'")
                    .split("/")
                    .last()
                    .expect("Could not extract submission id");

                let forum = item.find(Class("comment-nav-reply"))
                    .next()
                    .expect("Could not find class='comment-nav-reply' in html source")
                    .find(Name("a"))
                    .next()
                    .expect("Could not find element 'a' in html source")
                    .attr("href")
                    .expect("Could not find attribute 'href' in html element 'a'")
                    .split("/")
                    .nth(2)
                    .expect("Could not extract author")
                    .to_string();

                let body = item.find(Class("comment-body"))
                    .next()
                    .expect("Could not find class='comment-body' in html source")
                    .text();

                let inner = item.find(Class("comment-inner"))
                    .next()
                    .expect("Could not find class 'comment-inner' in html source");
                let date = inner.find(Name("time"))
                    .next()
                    .expect("Could not find element 'time' in html source")
                    .attr("datetime")
                    .expect("Could not find attribute 'datetime' in html element 'time'");
                let author = inner.find(Class("comment-user"))
                    .next()
                    .expect("Could not find class='comment-user' in html source")
                    .text();

                CommentInfo {
                    body: body,
                    date: DateTime::parse_from_rfc3339(date).expect("Submission date is not valid"),
                    author: author,
                    forum: forum,
                    id: id.parse().expect("Comment id is not valid"),
                    submission_id: submission_id.parse().expect("Submission id is not valid")
                }
            })
            .filter(|comment| {
                // Remove everything that's before the date specified
                if comment.date <= date {
                    date_encountered = true;
                    return false;
                }

                true
            }));

            if date_encountered {
                return Ok(comments);
            }

            // Find the url for the next page
            let next_element = match document.find(Class("pagination")).next() {
                Some(elem) => elem
                    .find(Class("next"))
                    .next()
                    .expect("Could not find class 'next' in html element 'pagination'")
                    .find(Name("a"))
                    .next()
                    .expect("Could not find name 'a' in html element 'next'"),
                // We are finished if there is no more pagination URL
                None => return Ok(comments)
            };
            let next_url = next_element
                .attr("href")
                .expect("Could not find attribute 'href' in element 'a'")
                .to_string();
            let url = self.root_url.clone().join(&*next_url)?;
            resp = self.do_get_request_url(url)?;
        }
    }

    /// Login as a user.
    /// This sets the session cookie and the csrf token which are bound to the user login.
    pub fn login(&mut self, username: &str, password: &str) -> Result<(), Box<Error>> {
        // First retreive the csrf_token and the session cookie
        let resp = self.do_get_request("login")?;

        // Get the csrf token
        let document = Document::from_read(resp)?;
        let csrf_token = document.find(Attr("name", "_csrf_token")).next()
            .expect("Could not find attribute name='_csrf_token' in html source")
            .attr("value")
            .expect("Could not find attribute 'value' in the '_csrf_token' html element");
        self.csrf_token = Some(csrf_token.to_string().clone());

        // Then use it to login
        // Create the form data
        let params = [("_username", username), ("_password", password), ("_csrf_token", csrf_token)];

        // And the form URL
        let resp = self.do_form_post_request("login_check", &params)?;

        let resp_url = Url::parse(resp.headers().get(reqwest::header::LOCATION)
                                  .expect("Header 'Location' is missing").to_str()?)?;

        // When a login is done successfully we should be automatically routed to the main page
        assert_eq!(resp_url, self.root_url);

        Ok(())
    }

    /// Reply on a comment.
    pub fn reply_comment(&mut self, submission_id: u64, comment_id: u64, comment: &str) -> Result<u64, Box<Error>> {
        // First get the proper full URL
        let sub_url = self.get_full_url(submission_id)?;

        let (partial_url,_) = sub_url.split_at(sub_url.rfind('/').expect("Could not find '/' character"));

        let new_url = format!("{}/comment/{}", partial_url, comment_id);

        // Then get the form token and the proper submit url
        let resp = self.do_get_request(&*new_url)?;

        // Get the submit form token
        let document = Document::from_read(resp)?;
        let form_token = document.find(Attr("name", "comment[_token]"))
            .next()
            .expect("Could not find attribute name='comment[_token]' in html source")
            .attr("value")
            .expect("Could not find attribute 'value' in the 'comment[_token]' html element");

        let params = [
            ("comment[_token]", form_token),
            ("comment[comment]", comment),
            ("comment[email]", ""),
            ("comment[submit]", "")
        ];

        let url = format!("{}/comment_post/{}", partial_url, comment_id);
        let resp = self.do_form_post_request(&*url, &params)?;
        let headers = resp.headers().clone();

        // Check if the new location is set
        if !headers.contains_key(reqwest::header::LOCATION) {
            // There is no location, so maybe the error is set
            let document = Document::from_read(resp)?;
            match document.find(Class("form__error")).next() {
                Some(err_elem) => panic!(format!("Submitting comment failed with error: {}", err_elem.text())),
                None => panic!("Submitting comment failed: Could not find location or error message")
            };
        }

        // Get the comment id, it's the last part of the location
        let comment_id = resp.headers()[reqwest::header::LOCATION]
            .to_str()?
            .split("/")
            .last()
            .expect("Could not split on '/' in 'Location' header");

        Ok(comment_id.parse()?)
    }

    /// Comment on a forum post.
    pub fn comment_post(&mut self, submission_id: u64, comment: &str) -> Result<u64, Box<Error>> {
        // First get the proper full URL
        let new_url = self.get_full_url(submission_id)?;

        // Then get the form token and the proper submit url
        let resp = self.do_get_request(&*new_url)?;

        // Get the submit form token
        let document = Document::from_read(resp)?;
        let form_token = document.find(Attr("name", "comment[_token]")).next()
            .expect("Could not find attribute name='comment[_token]' in html source")
            .attr("value")
            .expect("Could not find attribute 'value' in the 'comment[_token]' html element");
        // Get the URL of the form
        let url = document.find(And(Attr("name", "comment"), Class("comment-form"))).next()
            .expect("Could not find attribute name='comment' in html source")
            .attr("action")
            .expect("Could not find attribute 'action' in the 'comment' html element");

        // Create the form data
        let params = [
            ("comment[_token]", form_token),
            ("comment[comment]", comment),
            ("comment[email]", ""),
            ("comment[submit]", "")
        ];

        let resp = self.do_form_post_request(&*url, &params)?;
        let headers = resp.headers().clone();

        // Check if the new location is set
        if !headers.contains_key(reqwest::header::LOCATION) {
            // There is no location, so maybe the error is set
            let document = Document::from_read(resp)?;
            match document.find(Class("form__error")).next() {
                Some(err_elem) => panic!(format!("Submitting comment failed with error: {}", err_elem.text())),
                None => panic!("Submitting comment failed: Could not find location or error message")
            };
        }

        // Get the comment id, it's the last part of the location
        let comment_id = resp.headers()[reqwest::header::LOCATION].to_str()?
            .split("/").last()
            .expect("Could not split on '/' in 'Location' header");

        Ok(comment_id.parse()?)
    }

    /// Submit a forum post.
    pub fn submit_post(&mut self, forum: &str, url: &str, title: &str, body: &str) -> Result<u64, Box<Error>> {
        let submit_url = &*format!("submit/{}", forum);

        // First get the form token
        let resp = self.do_get_request(submit_url)?;

        // Get the submit form token
        let document = Document::from_read(resp)?;
        let form_token = document.find(Attr("name", "submission[_token]")).next()
            .expect("Could not find attribute name='submission[_token]' in html source")
            .attr("value")
            .expect("Could not find attribute 'value' in the 'submission[_token]' html element")
            .trim();

        let forum_id = document.find(Attr("selected", "selected")).next()
            .expect("Could not find attribute 'selected' in html source")
            .attr("value")
            .expect("Could not find attribute 'value' in the 'option' html element");

        // Create the form data
        let params = [
            ("submission[_token]", form_token),
            ("submission[url]", url),
            ("submission[title]", title),
            ("submission[body]", body),
            ("submission[forum]", forum_id),
            ("submission[email]", ""),
            ("submission[submit]", ""),
        ];

        let resp = self.do_form_post_request(submit_url, &params)?;
        let headers = resp.headers().clone();

        // Check if the post was successful
        let document = Document::from_read(resp)?;
        match document.find(Class("form__error")).next() {
            Some(err_elem) => panic!(format!("Submitting post failed with error: {}", err_elem.text())),
            // Could not find the error class so it's successful
            None => Ok(headers[reqwest::header::LOCATION]
                       .to_str()?
                       .split("/").nth(3)
                       .expect("Could not split on '/' in 'Location' header")
                       .parse()?)
        }
    }

    /// Edit a forum post.
    /// All the `Option<T>` parameters won't update if `None` is passed.
    pub fn edit_post(&mut self, submission_id: u64, url: Option<&str>, title: Option<&str>, body: Option<&str>) -> Result<u64, Box<Error>> {
        // First get the proper full URL
        let new_url = self.get_full_url(submission_id)?;

        // Then get the edit submission URL
        let resp = self.do_get_request(&*new_url)?;
        let document = Document::from_read(resp)?;
        // Then get the forum name
        let forum_name = document
            .find(Class("submission-forum")).next()
            .expect("Could not find class 'submission-forum' in html source")
            .text();
        let submit_url = format!("f/{}/edit_submission/{}", forum_name, submission_id);

        // Then get the form token
        let resp = self.do_get_request(&*submit_url)?;
        let document = Document::from_read(resp)?;
        let form_token = document
            .find(Attr("name", "submission[_token]")).next()
            .expect("Could not find attribute name='submission[_token]' in html source")
            .attr("value")
            .expect("Could not find attribute 'value' in the 'submission[_token]' html element")
            .trim();

        // Get the default values if they are not passed
        let url = match url {
            Some(url) => url.to_string(),
            None => document
                .find(Attr("name", "submission[url]")).next()
                .expect("Could not find attribute name='submission[url]' in html source")
                .text()
        };
        let title = match title {
            Some(title) => title.to_string(),
            None => document
                .find(Attr("name", "submission[title]")).next()
                .expect("Could not find attribute name='submission[title]' in html source")
                .text()
        };
        let body = match body {
            Some(body) => body.to_string(),
            None => document
                .find(Attr("name", "submission[body]")).next()
                .expect("Could not find attribute name='submission[body]' in html source")
                .text()
        };

        // Create the form data
        let params = [
            ("submission[_token]", form_token),
            ("submission[url]", &*url),
            ("submission[title]", &*title),
            ("submission[body]", &*body),
            ("submission[email]", ""),
            ("submission[submit]", ""),
            ("submission[userFlag]", "0"),
        ];

        let resp = self.do_form_post_request(&*submit_url, &params)?;
        let headers = resp.headers().clone();

        // Check if the post was successful
        let document = Document::from_read(resp)?;
        match document.find(Class("form__error")).next() {
            Some(err_elem) => panic!(format!("Submitting post failed with error: {}", err_elem.text())),
            // Could not find the error class so it's successful
            None => Ok(headers[reqwest::header::LOCATION]
                       .to_str()?
                       .split("/").nth(3)
                       .expect("Could not split on '/' in 'Location' header")
                       .parse()?)
        }
    }

    fn get_full_url(&mut self, submission_id: u64) -> Result<String, Box<Error>> {
        let resp = self.do_get_request(&*format!("{}", submission_id))?;

        let headers = resp.headers().clone();
        if !headers.contains_key(reqwest::header::LOCATION) {
            panic!("Could not find 'Location' header");
        }
        return Ok(String::from(resp.headers()[reqwest::header::LOCATION].to_str()?));
    }

    fn default_headers() -> HeaderMap {
        let mut headers = HeaderMap::new();

        headers.insert(reqwest::header::USER_AGENT, HeaderValue::from_static("RustBot"));

        headers
    }

    fn do_get_request_url(&mut self, url: Url) -> Result<reqwest::Response, reqwest::Error> {
        let request = self.http_client.get(url)
            // Set the cookie header
            .headers(self.construct_headers())
            .build()?;
        let resp = self.http_client.execute(request)?.error_for_status()?;
        self.add_cookies(&resp);

        Ok(resp)
    }

    fn do_get_request(&mut self, path: &str) -> Result<reqwest::Response, reqwest::Error> {
        let mut url = self.root_url.clone();
        url.set_path(path);
        let request = self.http_client.get(url)
            // Set the cookie header
            .headers(self.construct_headers())
            .build()?;
        let resp = self.http_client.execute(request)?.error_for_status()?;
        if resp.status().is_server_error() {
            panic!("Received a server error for GET in path {}", path);
        }
        if resp.status().is_client_error() {
            panic!("Received a client error for GET in path {}", path);
        }
        self.add_cookies(&resp);

        Ok(resp)
    }

    fn do_form_post_request(&mut self, path: &str, params: &[(&str, &str)]) -> Result<reqwest::Response, reqwest::Error> {
        let mut url = self.root_url.clone();
        url.set_path(path);
        let request = self.http_client.post(url)
            // Set the cookie header
            .headers(self.construct_headers())
            // Add the form info
            .form(&params)
            .build()?;
        let resp = self.http_client.execute(request)?.error_for_status()?;
        if resp.status().is_server_error() {
            panic!(format!("Received a server error for POST in path {}", path));
        }
        self.add_cookies(&resp);

        Ok(resp)
    }

    fn construct_headers(&self) -> HeaderMap {
        let mut headers = HeaderMap::new();

        // Add all the cookies
        self.session_cookies.iter().for_each(|cookie| {
            let cookie_str = format!("{}={}", cookie.name().clone(), cookie.value().clone());
            headers.insert(reqwest::header::COOKIE, HeaderValue::from_str(&cookie_str)
                           .expect("Could not create 'Cookie' HeaderValue from string"));
        });

        headers.insert(reqwest::header::HOST,
            HeaderValue::from_str(self.root_url.clone().host_str()
                                  .expect("Could not find host string in 'Host' header"))
            .expect("Could not create 'Host' header from HeaderValue"));

        headers.insert(reqwest::header::REFERER,
            HeaderValue::from_str(self.root_url.clone().as_str())
            .expect("Could not create 'Referer' header from HeaderValue"));

        headers
    }

    fn add_cookies(&mut self, response: &reqwest::Response) {
        response.headers().get_all(reqwest::header::SET_COOKIE).iter().for_each(|raw_cookie| {
            let raw_cookie_str = raw_cookie.to_str()
                .expect("Cookie could not be converted to string");
            let cookie = Cookie::parse(raw_cookie_str)
                .expect("Cookie could not be parsed to string");
            self.session_cookies.add(cookie.into_owned());
        });
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn constructor() {
        Client::new("https://example.com").unwrap();
    }

    #[test]
    #[should_panic]
    fn constructor_wrong_url() {
        Client::new("this is not a valid url").unwrap();
    }
}
