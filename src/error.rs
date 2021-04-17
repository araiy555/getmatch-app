use std::error;

/// A `Result` type alias for this crate's `Error` type.
pub type Result<T> = ::std::result::Result<T, Error>;

/// An error that encapsulates all possible errors in this crate.
#[derive(Debug)]
pub enum Error {
    /// A network error that occured while trying to talk to the Postmill client.
    Network(String),
    /// A error that occurred while parsing returned HTML.
    HTML(String),
}

impl From<io::Error> for Error {
    #[inline]
    fn from(err: io::Error) -> Error {
        Error::Io(err)
    }
}

impl From<raw::Error> for Error {
    #[inline]
    fn from(err: raw::Error) -> Error {
        Error::Fst(err)
    }
}

impl fmt::Display for Error {
    fn fmt(&self, f: &mut fmt::Formatter) -> fmt::Result {
        use self::Error::*;
        match *self {
            Fst(ref err) => err.fmt(f),
            Io(ref err) => err.fmt(f),
        }
    }
}

impl error::Error for Error {
    fn description(&self) -> &str {
        use self::Error::*;
        match *self {
            Fst(ref err) => err.description(),
            Io(ref err) => err.description(),
        }
    }

    fn cause(&self) -> Option<&error::Error> {
        use self::Error::*;
        match *self {
            Fst(ref err) => Some(err),
            Io(ref err) => Some(err),
        }
    }
}
