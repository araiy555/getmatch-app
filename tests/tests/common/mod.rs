pub fn active_url() -> Result<String, String> {
    if reqwest::get("http://xn--q6c.net").is_ok() {
        return Ok("http://xn--q6c.net".to_string());
    }
    if reqwest::get("https://voice404.com").is_ok() {
        return Ok("https://voice404.com".to_string());
    }
    if reqwest::get("https://www.jstpst.net").is_ok() {
        return Ok("https://www.jstpst.net".to_string());
    }
    if reqwest::get("https://raddle.me").is_ok() {
        return Ok("https://raddle.me".to_string());
    }
    return Err("No alive Postmill server found".to_string());
}
