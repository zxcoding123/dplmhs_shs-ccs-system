<div class="modal fade" id="notificationModal" tabindex="-1"
  aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">Notifications</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal"
          aria-label="Close"></button>
      </div>
      <div class="modal-body">

        <div class="d-flex align-items-center">
          <span>Notifications</span>
          <div class=" ms-auto" aria-hidden="true"><a href
              class="nav-ham-link"> View All</a> | <a href
              class="nav-ham-link"> Read All</a></div>
        </div>

        <br>

        <div class="row ">
          <div class="col-sm-2 text-center">
            <h1><i class="bi bi-bell-fill"></i></h1>
          </div>
          <div class="col">
            <h5>You have received a new notification!</h5>
            <p>Test notification!</p>
          </div>
        </div>

        <br>

        <div class="row ">
          <div class="col-sm-2 text-center">
            <h1><i class="bi bi-bell-fill"></i></h1>
          </div>
          <div class="col">
            <h5>You have received a new notification!</h5>
            <p>Test notification!</p>
          </div>
        </div>

      </div>

    </div>
  </div>
</div>

<div class="modal fade" id="messageModal" tabindex="-1"
  aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">Chats</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal"
          aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <form>
          <input type="text" name="search" placeholder="Input person here"
            required class="input-search">
        </form>
        <br>
        <div class="row msg align-items-center"
          data-bs-target="#actualMessageModal" data-bs-toggle="modal">
          <div class="col-sm-3 text-center"
            style="border-right: 1px solid black;">
            <h1><i class="bi bi-person-fill"></i></h1>
            <span>Jason Catadman</span>
          </div>
          <div class="col">
            <p><em>You: Sure sir Catadman. I will work on that right
                now.</em></p>
          </div>
        </div>

        <br>

        <div class="row unread msg align-items-center">
          <div class="col-sm-3 text-center"
            style="border-right: 1px solid black;">
            <h1><i class="bi bi-person-fill"></i></h1>
            <span>Ceed Lorenzo</span>
          </div>
          <div class="col">
            <p><em>Lorenzo: Good afternoon sir, ask ko lang if available po
                si...</em></p>
          </div>
        </div>

      </div>

    </div>
  </div>
</div>

<div class="modal fade" id="actualMessageModal" tabindex="-1"
  aria-labelledby="exampleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h1 class="modal-title fs-5" id="exampleModalLabel">Chats</h1>
        <button type="button" class="btn-close" data-bs-dismiss="modal"
          aria-label="Close"></button>
      </div>
      <div class="modal-body" id="chatBody">
        <div class="time text-center grey">
          2:09 PM - 8/11/2024
        </div>
        <br>
        <div class="row sender">
          <div class="col">
            <i class="bi bi-person-fill"></i>
            <div class="message">
              <span>Hi, this is Jason Catadman. I'd like to switch from Web
                Technologies to Software Engineering, thanks!</span>
            </div>
          </div>
        </div>
        <br>
        <div class="row receiver">
          <div class="col">
            <div class="message">
              <span>Sure sir Catadman. I will work on that right now.</span>
            </div>
            <i class="bi bi-person"></i>
          </div>
        </div>
        <br>
      </div>

      <div class="modal-footer">
        <form id="messageForm">
          <div class="d-flex align-items-center">
            <textarea id="messageInput" cols="45"></textarea>
            <div class="ms-auto" aria-hidden="true" style="margin-left: 10px">
              <input type="submit" value="Send">
            </div>
          </div>
      </div>
      </form>

    </div>
  </div>
</div>